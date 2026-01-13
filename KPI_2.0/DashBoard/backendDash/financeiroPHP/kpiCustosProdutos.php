<?php
// custosProdutos.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header("Content-Type: application/json; charset=UTF-8");
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';

/**
 * Normaliza data (YYYY-MM-DD). Se inválida/ausente, usa fallback.
 */
function norm_date($d, $fallback) {
  if (!$d) return $fallback;
  $t = strtotime($d);
  return $t ? date("Y-m-d", $t) : $fallback;
}

try {
  // Datas (considerando o dia todo)
  $dataIni = $_POST['data_inicial'] ?? '2000-01-01';
  $dataFim = $_POST['data_final']   ?? date('Y-m-d');
  $dataInicial = norm_date($dataIni, '2000-01-01') . " 00:00:00";
  $dataFinal   = norm_date($dataFim, date('Y-m-d')) . " 23:59:59";

  /**
   * Lógica:
   * 1) Pega os numeros de orçamento (DISTINCT) em reparo_parcial no período (data_solicitacao_nf).
   * 2) Junta com apontamentos_gerados (produto, servico).
   * 3) Junta com produtos_catalogo para obter o valor do produto.
   * 4) Agrega por produto: qtd_somado, qtd_nao_somado, valor_somado, serviços distintos.
   */
  $sql = "
    WITH orcamentos AS (
      SELECT DISTINCT rp.numero_orcamento
      FROM reparo_parcial rp
      WHERE rp.data_solicitacao_nf BETWEEN ? AND ?
        AND rp.numero_orcamento IS NOT NULL
        AND rp.numero_orcamento <> ''
    )
    SELECT 
      ag.produto                                              AS produto,
      pc.preco_venda                                                AS valor_unit,       -- pode ser NULL
      SUM(CASE WHEN UPPER(TRIM(ag.servico)) = 'REPARO FORA DA GARANTIA' THEN 1 ELSE 0 END) AS qtd_somado,
      SUM(CASE WHEN UPPER(TRIM(ag.servico)) = 'REPARO FORA DA GARANTIA' THEN 0 ELSE 1 END) AS qtd_nao_somado,
      SUM(CASE 
            WHEN UPPER(TRIM(ag.servico)) = 'REPARO FORA DA GARANTIA' 
            THEN COALESCE(pc.preco_venda,0) 
            ELSE 0 
          END)                                                AS valor_somado,
      GROUP_CONCAT(DISTINCT ag.servico ORDER BY ag.servico SEPARATOR ', ')       AS servicos,
      SUM(CASE WHEN pc.preco_venda IS NULL THEN 1 ELSE 0 END)       AS qtd_sem_preco,
      SUM(CASE WHEN pc.preco_venda IS NULL THEN 0 ELSE 1 END)       AS qtd_com_preco
    FROM apontamentos_gerados ag
    INNER JOIN orcamentos o ON o.numero_orcamento = ag.orcamento
    LEFT  JOIN produtos_catalogo pc ON pc.produto = ag.produto
    WHERE ag.produto IS NOT NULL AND ag.produto <> ''
    GROUP BY ag.produto, pc.preco_venda 
    ORDER BY valor_somado DESC, ag.produto ASC
  ";

  // MySQL não suporta CTE em algumas versões antigas. Se a sua for antiga,
  // troque a CTE por um IN (subquery) igual ao abaixo:
  // $sql = "
  //   SELECT ... FROM apontamentos_gerados ag
  //   WHERE ag.numero_orcamento IN (
  //     SELECT DISTINCT rp.numero_orcamento 
  //     FROM reparo_parcial rp
  //     WHERE rp.data_solicitacao_nf BETWEEN ? AND ?
  //       AND rp.numero_orcamento IS NOT NULL
  //       AND rp.numero_orcamento <> ''
  //   )
  //   LEFT JOIN produtos_catalogo pc ...
  //   GROUP BY ...
  // ";

  $stmt = $conn->prepare($sql);
  if (!$stmt) throw new Exception("Erro na preparação da consulta: " . $conn->error);
  $stmt->bind_param("ss", $dataInicial, $dataFinal);
  $stmt->execute();
  $res = $stmt->get_result();

  $produtos = [];
  $total = 0.0;
  $itensSomados = 0;
  $itensNaoSomados = 0;
  $totalComPreco = 0;
  $totalSemPreco = 0;

  while ($row = $res->fetch_assoc()) {
    $valorUnit = is_null($row['valor_unit']) ? null : (float)$row['valor_unit'];
    $qSomado   = (int)$row['qtd_somado'];
    $qNao      = (int)$row['qtd_nao_somado'];
    $vSomado   = (float)$row['valor_somado'];

    $produtos[] = [
      'produto'        => $row['produto'] ?: '-',
      'valor_unit'     => $valorUnit,                // mantém numérico (formatação no JS)
      'qtd_somado'     => $qSomado,
      'qtd_nao_somado' => $qNao,
      'valor_somado'   => $vSomado,
      'servicos'       => $row['servicos'] ?: '',
      'qtd_com_preco'  => (int)$row['qtd_com_preco'],
      'qtd_sem_preco'  => (int)$row['qtd_sem_preco']
    ];

    $total            += $vSomado;
    $itensSomados     += $qSomado;
    $itensNaoSomados  += $qNao;
    $totalComPreco    += (int)$row['qtd_com_preco'];
    $totalSemPreco    += (int)$row['qtd_sem_preco'];
  }

  echo json_encode([
    'ok'   => true,
    'data' => [
      'total'   => $total,
      'resumo'  => [
        'itens_somados'     => $itensSomados,
        'itens_nao_somados' => $itensNaoSomados,
        'itens_com_preco'   => $totalComPreco,
        'itens_sem_preco'   => $totalSemPreco,
      ],
      'produtos' => $produtos
    ]
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  echo json_encode(['ok' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>