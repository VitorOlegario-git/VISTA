<?php
/**
 * sem_conserto_produtos.php
 * Gráfico empilhado por serviço + Tabela MODELO x PRODUTO (mesmo produto do gráfico).
 * ✅ Inclui o campo "servico" em cada linha de data.tabela para sincronizar com a legenda.
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
header("Content-Type: application/json; charset=UTF-8");
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';

/* ===== Helpers ===== */
function respond($ok, $data = null, $msg = "") {
  echo json_encode(["ok"=>$ok, "message"=>$msg, "data"=>$data], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}
function norm_date($d,$fallback){
  if(!$d) return $fallback;
  $t = strtotime($d);
  return $t ? date("Y-m-d", $t) : $fallback;
}
function normalize_text($s){
  $s = trim((string)$s); if($s==="") return "";
  $no = @iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s); if($no!==false) $s=$no;
  $s = mb_strtoupper($s,'UTF-8');
  $s = preg_replace('/\s+/', ' ', $s);
  return trim($s);
}
/* 🔧 Normaliza nome do PRODUTO para usar IGUAL no gráfico e na tabela */
function canonical_product($raw){
  $p = normalize_text($raw);           // maiúsculas e espaços únicos
  // padroniza hífen com espaços
  $p = preg_replace('/\s*-\s*/', ' - ', $p);
  // remove espaços duplicados novamente
  $p = preg_replace('/\s+/', ' ', $p);
  return trim($p);
}
/* Mapeia 1 item de serviço para as 3 categorias canônicas */
function canonical_service($raw){
  $s = normalize_text($raw); if($s==="") return null;
  $c = preg_replace('/[^A-Z0-9]/','',$s);
  if (strpos($c,'SEMREPARO')!==false && strpos($c,'FORA')!==false && strpos($c,'GARAN')!==false)
    return "SEM REPARO - FORA DA GARANTIA";
  if (strpos($c,'SEMREPARO')!==false && preg_match('/SUBS(T|TI|TITU)?/',$c) && (strpos($c,'EMGAR')!==false || strpos($c,'GARAN')!==false))
    return "SEM REPARO - SUBST. EM GARANTIA";
  if (preg_match('/REJEITAD|RECUSAD/',$c) && strpos($c,'CLIENTE')!==false)
    return "REJEITADO PELO CLIENTE";
  return null;
}

/* ===== Entrada ===== */
$dataIni = $_POST['data_inicial'] ?? '2000-01-01';
$dataFim = $_POST['data_final']   ?? date('Y-m-d');
$dataInicial = norm_date($dataIni,'2000-01-01')." 00:00:00";
$dataFinal   = norm_date($dataFim, date('Y-m-d'))." 23:59:59";

try{
  if(!isset($conn) || !$conn instanceof mysqli) respond(false,null,"Falha de conexão.");

  // 1) Orçamentos do período
  $sqlOrc = "
    SELECT DISTINCT numero_orcamento
    FROM reparo_parcial
    WHERE data_solicitacao_nf BETWEEN ? AND ?
      AND numero_orcamento IS NOT NULL AND numero_orcamento <> ''
  ";
  $st=$conn->prepare($sqlOrc); if(!$st) respond(false,null,"Erro prepare orçamentos: ".$conn->error);
  $st->bind_param("ss",$dataInicial,$dataFinal);
  $st->execute(); $r=$st->get_result();
  $orc=[]; while($row=$r->fetch_assoc()){ $v=trim((string)$row['numero_orcamento']); if($v!=="") $orc[]=$v; }
  $st->close();

  if(!$orc){
    respond(true, [
      "labels"=>[], "totais_por_produto"=>[], "breakdown"=>new stdClass(),
      "tabela"=>[], "modelos"=>[], "orcamentos_considerados"=>0
    ], "Nenhum orçamento no período.");
  }

  // 2) Apontamentos (servico, produto, modelo)
  $ph = implode(",", array_fill(0,count($orc),"?" ));
  $sqlAp = "SELECT servico, produto, modelo FROM apontamentos_gerados WHERE orcamento IN ($ph)";
  $st=$conn->prepare($sqlAp); if(!$st) respond(false,null,"Erro prepare apontamentos: ".$conn->error);
  $types=str_repeat("s",count($orc)); $st->bind_param($types, ...$orc);
  $st->execute(); $r2=$st->get_result();

  $breakdown = [
    "SEM REPARO - FORA DA GARANTIA" => [],
    "SEM REPARO - SUBST. EM GARANTIA" => [],
    "REJEITADO PELO CLIENTE" => []
  ];
  $totaisPorProduto = [];

  // 🔁 Agora a tabela agrega por (modelo, produto, servico) para permitir filtro por legenda
  $tabelaMap = []; // chave: modelo|produto|servico -> qtd
  $modeloSet = [];

  while($row=$r2->fetch_assoc()){
    $produtoRaw = (string)($row['produto'] ?? "");
    $produto    = canonical_product($produtoRaw);   // ← usar sempre este
    $modelo     = normalize_text($row['modelo'] ?? "NÃO INFORMADO");
    if ($produto==="") continue;

    $servCampo = (string)($row['servico'] ?? "");
    if ($servCampo==="") continue;

    // divide múltiplos serviços por vírgula/;/ e /
    $itens = preg_split('/[,;\/]+/', $servCampo);
    foreach($itens as $item){
      $canon = canonical_service($item);
      if(!$canon) continue;

      // gráfico (por produto) — usa o MESMO $produto canônico
      if(!isset($breakdown[$canon][$produto])) $breakdown[$canon][$produto]=0;
      $breakdown[$canon][$produto]++;
      if(!isset($totaisPorProduto[$produto])) $totaisPorProduto[$produto]=0;
      $totaisPorProduto[$produto]++;

      // tabela (modelo x produto x servico)
      $k = $modelo."|".$produto."|".$canon;
      if(!isset($tabelaMap[$k])) $tabelaMap[$k]=0;
      $tabelaMap[$k]++;

      if ($modelo !== "") $modeloSet[$modelo] = true;
    }
  }
  $st->close();

  // ordenar produtos desc (gráfico)
  arsort($totaisPorProduto);
  $labels = array_keys($totaisPorProduto);
  $values = array_values($totaisPorProduto);

  foreach($breakdown as $srv=>$map){ arsort($map); $breakdown[$srv]=$map; }

  // montar linhas da tabela (modelo, produto, servico, quantidade)
  $tabela = [];
  foreach($tabelaMap as $k=>$q){
    [$modelo,$produto,$servico] = explode("|",$k,3);
    $tabela[] = [
      "modelo"     => $modelo,
      "produto"    => $produto,
      "servico"    => $servico,   // ✅ usado pelo front para filtrar quando clica na legenda
      "quantidade" => (int)$q
    ];
  }
  // Ordena: maior quantidade primeiro; tie-break por modelo/produto
  usort($tabela, function($a,$b){
    if($a["quantidade"]==$b["quantidade"]){
      $c = strcasecmp($a["modelo"],$b["modelo"]);
      if($c!==0) return $c;
      $d = strcasecmp($a["produto"],$b["produto"]);
      if($d!==0) return $d;
      return strcasecmp($a["servico"],$b["servico"]);
    }
    return ($a["quantidade"]<$b["quantidade"]) ? 1 : -1;
  });

  $modelos = array_keys($modeloSet);
  sort($modelos, SORT_NATURAL | SORT_FLAG_CASE);

  respond(true, [
    "labels"=>$labels,
    "totais_por_produto"=>$values,
    "breakdown"=>$breakdown,
    "tabela"=>$tabela,                  // ← agora cada item tem "servico"
    "modelos"=>$modelos,
    "orcamentos_considerados"=>count($orc)
  ]);

}catch(Throwable $e){
  respond(false,null,"Erro no backend: ".$e->getMessage());
}
?>
