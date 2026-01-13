<?php
/**
 * Qualidade.php — versão com pré-check (only_check)
 * - Pré-check: verifica substituição e NÃO grava (only_check=1)
 * - Save: valida todos os campos, grava, e retorna também a verificação
 */

declare(strict_types=1);

session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// ===== Robustez =====
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
  http_response_code(500);
  echo json_encode(["success"=>false,"error"=>"PHP Error: $errstr at $errfile:$errline"]); exit;
});
set_exception_handler(function (Throwable $e) {
  http_response_code(500);
  echo json_encode(["success"=>false,"error"=>"Exception: ".$e->getMessage()]); exit;
});

// ===== Sessão =====
$tempo_limite = 1200;
if (isset($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > $tempo_limite) {
  session_unset(); session_destroy();
  http_response_code(440);
  echo json_encode(["success"=>false,"error"=>"Sessão expirada."]); exit;
}
if (!isset($_SESSION['username'])) {
  http_response_code(401);
  echo json_encode(["success"=>false,"error"=>"Sessão não iniciada."]); exit;
}
$_SESSION['last_activity'] = time();

// ===== Conexão =====
require_once dirname(__DIR__) . '/conexao.php';
/** @var mysqli $conn */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

// ===== Utils =====
function limpar(string $v): string {
  return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8');
}
function buscarNumeroOrcamento(mysqli $conn, string $cnpj, string $nf): ?string {
  $sql = "SELECT numero_orcamento
            FROM resumo_geral
           WHERE cnpj = ? AND nota_fiscal = ?
        ORDER BY data_ultimo_registro DESC
           LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $cnpj, $nf);
  $stmt->execute();
  $res = $stmt->get_result();
  $numero = null;
  if ($row = $res->fetch_assoc()) { $numero = $row['numero_orcamento'] ?? null; }
  $stmt->close();
  return $numero ?: null;
}
function buscarImeisDevolucao(mysqli $conn, string $numero_orcamento): array {
  $sql = "SELECT imei_devol FROM apontamentos_gerados WHERE orcamento = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $numero_orcamento);
  $stmt->execute();
  $res = $stmt->get_result();
  $set = [];
  while ($row = $res->fetch_assoc()) {
    $val = trim((string)($row['imei_devol'] ?? ''));
    if ($val === '' || $val === '-') continue;
    if (preg_match_all('/\b\d{15}\b/', $val, $m)) {
      foreach ($m[0] as $imei) $set[$imei] = true;
    }
  }
  $stmt->close();
  return array_keys($set);
}

// ===== Método =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(["success"=>false,"error"=>"Método inválido. Apenas POST é aceito."]); exit;
}

// ===== Entrada =====
$only_check          = isset($_POST['only_check']) && $_POST['only_check'] === '1';
$cnpj                = limpar($_POST['cnpj'] ?? '');
$nota_fiscal         = limpar($_POST['nota_fiscal'] ?? '');
$data_inicio_qualidade = trim((string)($_POST['data_inicio_qualidade'] ?? ''));
$data_envio_expedicao  = trim((string)($_POST['data_envio_expedicao'] ?? '')); // opcional
$razao_social        = limpar($_POST['razao_social'] ?? '');
$quantidade          = (int)($_POST['quantidade'] ?? 0);
$quantidade_parcial  = (int)($_POST['quantidade_parcial'] ?? 0);
$operacao_origem     = limpar($_POST['operacao_origem'] ?? '');
$operacao_destino    = limpar($_POST['operacao_destino'] ?? '');
$setor               = limpar($_POST['setor'] ?? '');
$operador            = limpar($_SESSION['username'] ?? 'Desconhecido');
$observacoes         = limpar($_POST['obs'] ?? '');
$nota_fiscal_retorno = limpar($_POST['nota_fiscal_retorno'] ?? '');

// ===== Validação =====
// Pré-check: só precisa CNPJ + NF
if ($only_check) {
  if ($cnpj === '' || $nota_fiscal === '') {
    http_response_code(400);
    echo json_encode(["success"=>false,"error"=>"Pré-cheque requer CNPJ e NF."]); exit;
  }

  $numero_orcamento = buscarNumeroOrcamento($conn, $cnpj, $nota_fiscal);
  $imeis = []; $has = false; $reason = "ok";
  if ($numero_orcamento) {
    $imeis = buscarImeisDevolucao($conn, $numero_orcamento);
    $has = count($imeis) > 0;
  } else {
    $reason = "sem_numero_orcamento";
  }
  $conn->close();
  echo json_encode([
    "success" => true,
    "only_check" => true,
    "substitution" => [
      "checked" => true,
      "has_substitution" => $has,
      "numero_orcamento" => $numero_orcamento,
      "imeis" => $imeis,
      "reason" => $reason
    ]
  ]);
  exit;
}

// Save normal: valida tudo
if (
  $cnpj === '' || $nota_fiscal === '' || $razao_social === '' ||
  $operacao_origem === '' || $operacao_destino === '' || $setor === '' ||
  $data_inicio_qualidade === ''
) {
  http_response_code(400);
  echo json_encode(["success"=>false,"error"=>"Campos obrigatórios ausentes."]); exit;
}

// ===== UPDATE =====
$sqlUpdate = "UPDATE qualidade_registro SET
                razao_social = ?,
                data_inicio_qualidade = ?,
                data_envio_expedicao = NULLIF(?, ''),
                quantidade = ?,
                quantidade_parcial = ?,
                operacao_origem = ?,
                operacao_destino = ?,
                setor = ?,
                operador = ?,
                observacoes = ?,
                nota_fiscal_retorno = ?,
                data_cadastro = NOW()
              WHERE cnpj = ? AND nota_fiscal = ?";

// checagem de placeholders/tipos
preg_match_all('/\?/', $sqlUpdate, $m);
$placeholders = count($m[0]); // 13
$types = 'sss' . 'ii' . str_repeat('s', 8); // s,s,s,i,i,s,s,s,s,s,s,s,s  -> 13
$params = [
  &$razao_social,
  &$data_inicio_qualidade,
  &$data_envio_expedicao,
  &$quantidade,
  &$quantidade_parcial,
  &$operacao_origem,
  &$operacao_destino,
  &$setor,
  &$operador,
  &$observacoes,
  &$nota_fiscal_retorno,
  &$cnpj,
  &$nota_fiscal
];

if ($placeholders !== strlen($types) || $placeholders !== count($params)) {
  http_response_code(500);
  echo json_encode([
    "success"=>false,
    "error"=>"Placeholders/types/params mismatch",
    "debug"=>[
      "placeholders"=>$placeholders,
      "types_len"=>strlen($types),
      "params_len"=>count($params),
      "types"=>$types
    ]
  ]);
  exit;
}

$stmt = $conn->prepare($sqlUpdate);
$okBind = $stmt->bind_param($types, ...$params);
if (!$okBind) { http_response_code(500); echo json_encode(["success"=>false,"error"=>"Erro bind_param(update): ".$stmt->error]); exit; }
if (!$stmt->execute()) { http_response_code(500); echo json_encode(["success"=>false,"error"=>"Erro execute(update): ".$stmt->error]); exit; }
$linhasAfetadas = $stmt->affected_rows;
$stmt->close();

// ===== Verificação pós-save (opcional, para mostrar no retorno final) =====
$numero_orcamento = buscarNumeroOrcamento($conn, $cnpj, $nota_fiscal);
$imeisSubst = []; $hasSubstitution = false; $reason = "ok";
if ($numero_orcamento) {
  $imeisSubst = buscarImeisDevolucao($conn, $numero_orcamento);
  $hasSubstitution = count($imeisSubst) > 0;
} else {
  $reason = "sem_numero_orcamento";
}
$conn->close();

echo json_encode([
  "success"=>true,
  "message"=>"Dados atualizados com sucesso.",
  "rows"=>$linhasAfetadas,
  "redirect"=>"https://kpi.stbextrema.com.br/BackEnd/cadastro_realizado.php",
  "substitution"=>[
    "checked"=>true,
    "has_substitution"=>$hasSubstitution,
    "numero_orcamento"=>$numero_orcamento,
    "imeis"=>$imeisSubst,
    "reason"=>$reason
  ]
]);
exit;
