<?php
// Ativa exibição de erros e inicia o buffer de saída
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

header('Content-Type: application/json');

session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

$tempo_limite = 1200; // 20 minutos

// Verifica inatividade
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $tempo_limite) {
    session_unset();
    session_destroy();
    echo json_encode(["success" => false, "error" => "Sessão expirada."]);
    exit();
}

// Verifica se a sessão está ativa
if (!isset($_SESSION['username'])) {
    echo json_encode(["success" => false, "error" => "Sessão não iniciada."]);
    exit();
}

$_SESSION['last_activity'] = time();

// Conexão com o banco de dados
require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

// Função para limpar entradas
function limparTexto($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

// Função para extrair campos ignorando letras maiúsculas/minúsculas
function getCampo($row, $campo) {
    foreach ($row as $k => $v) {
        if (strtoupper($k) === strtoupper($campo)) {
            return $v;
        }
    }
    return '';
}

// Verifica se é POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "error" => "Método inválido. Apenas POST é aceito."]);
    exit();
}

// Recebe o corpo JSON
$input = json_decode(file_get_contents("php://input"), true);
$jsonData = $input['jsonData'] ?? [];
$entrada_id = trim($input['entrada_id'] ?? '');

// Verifica se os dados são válidos
if (empty($jsonData) || empty($entrada_id)) {
    echo json_encode(["success" => false, "error" => "Dados do Excel ou entrada_id ausentes."]);
    exit();
}

// Prepara o statement SQL
$stmt = $conn->prepare("INSERT INTO apontamentos_gerados (
    entrada_id, imei, modelo, garantia, imei_devol, reclamacao,
    produto, servico, ocorrencia, cond_garantia_violada, orcamento
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    echo json_encode(["success" => false, "error" => "Erro ao preparar a inserção: " . $conn->error]);
    exit();
}

$erros = [];

foreach ($jsonData as $index => $row) {
    $imei = limparTexto(getCampo($row, 'IMEI'));
    $modelo = limparTexto(getCampo($row, 'MODELO'));
    $garantia = limparTexto(getCampo($row, 'GARANTIA'));
    $imei_devol = limparTexto(getCampo($row, 'IMEI_DEVOL'));
    $reclamacao = limparTexto(getCampo($row, 'RECLAMAÇÃO'));
    $produto = limparTexto(getCampo($row, 'PRODUTO'));
    $servico = limparTexto(getCampo($row, 'SERVIÇO'));
    $ocorrencia = limparTexto(getCampo($row, 'OCORRÊNCIA'));
    $cond_garantia_violada = limparTexto(getCampo($row, 'COND. GARANTIA VIOLADA'));
    $orcamento = limparTexto(getCampo($row, 'ORÇAM'));

    $stmt->bind_param(
        "sssssssssss",
        $entrada_id, $imei, $modelo, $garantia, $imei_devol, $reclamacao,
        $produto, $servico, $ocorrencia, $cond_garantia_violada, $orcamento
    );

    if (!$stmt->execute()) {
        $erros[] = "Linha " . ($index + 1) . ": " . $stmt->error;
    }
}

// Libera recursos
$stmt->close();
$conn->close();

// Captura qualquer saída inesperada
$saidaIndesejada = ob_get_contents();
ob_end_clean();

if (!empty($saidaIndesejada)) {
    echo json_encode([
        "success" => false,
        "error" => "Saída inesperada do servidor: " . trim($saidaIndesejada)
    ]);
} elseif (count($erros) > 0) {
    echo json_encode(["success" => false, "error" => implode(" | ", $erros)]);
} else {
    echo json_encode(["success" => true, "message" => "Todos os apontamentos foram cadastrados com sucesso."]);
}
?>
