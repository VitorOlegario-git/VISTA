<?php
session_start();

// Headers para controle de cache e tipo de resposta
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
header("Content-Type: application/json");

// Ativa erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inicia o buffer para capturar qualquer saída indesejada
ob_start();

// Verifica sessão
$tempo_limite = 1200;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $tempo_limite) {
    session_unset();
    session_destroy();
    http_response_code(401);
    echo json_encode(["error" => "Sessão expirada"]);
    exit();
}
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(["error" => "Usuário não autenticado"]);
    exit();
}
$_SESSION['last_activity'] = time();

require_once dirname(__DIR__) . '/conexao.php';

// Função de limpeza
function limpar($valor) {
    return htmlspecialchars(strip_tags(trim($valor)), ENT_QUOTES, 'UTF-8');
}

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método não permitido. Use POST."]);
    exit();
}

// Sanitiza os dados recebidos
$cnpj = limpar($_POST['cnpj'] ?? '');
$nota_fiscal = limpar($_POST['nota_fiscal'] ?? '');
$razao_social = limpar($_POST['razao_social'] ?? '');
$data_envio_expedicao = trim($_POST['data_envio_expedicao'] ?? '');
$data_envio_cliente = trim($_POST['data_envio_cliente'] ?? '');
$quantidade = (int)($_POST['quantidade'] ?? 0);
$codigo_rastreio_envio = limpar($_POST['codigo_rastreio_envio'] ?? '');
$operacao_origem = limpar($_POST['operacao_origem'] ?? '');
$operacao_destino = limpar($_POST['operacao_destino'] ?? '');
$setor = limpar($_POST['setor'] ?? '');
$operador = limpar($_SESSION['username'] ?? 'Desconhecido');
$observacoes = limpar($_POST['obs'] ?? '');
$nota_fiscal_retorno = limpar($_POST['nota_fiscal_retorno'] ?? '');

// Verificação básica
if (empty($cnpj) || empty($nota_fiscal) || empty($razao_social)) {
    http_response_code(400);
    echo json_encode(["error" => "Campos obrigatórios não preenchidos."]);
    exit();
}

// Verifica se o registro já existe
$stmt_check = $conn->prepare("SELECT id FROM expedicao_registro WHERE cnpj = ? AND nota_fiscal = ?");
$stmt_check->bind_param("ss", $cnpj, $nota_fiscal);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows > 0) {
    // Atualiza
    $stmt = $conn->prepare("
        UPDATE expedicao_registro SET 
            razao_social = ?, data_envio_expedicao = ?, data_envio_cliente = ?, quantidade = ?, 
            codigo_rastreio_envio = ?, operacao_origem = ?, operacao_destino = ?, setor = ?, 
            operador = ?, observacoes = ?, nota_fiscal_retorno = ?
        WHERE cnpj = ? AND nota_fiscal = ?
    ");
    $stmt->bind_param(
        "sssisssssssss",
        $razao_social, $data_envio_expedicao, $data_envio_cliente, $quantidade,
        $codigo_rastreio_envio, $operacao_origem, $operacao_destino, $setor,
        $operador, $observacoes, $nota_fiscal_retorno, $cnpj, $nota_fiscal
    );
} else {
    // Insere
    $stmt = $conn->prepare("
        INSERT INTO expedicao_registro (
            cnpj, nota_fiscal, razao_social, data_envio_expedicao, data_envio_cliente,
            quantidade, codigo_rastreio_envio, operacao_origem, operacao_destino,
            setor, operador, observacoes, nota_fiscal_retorno
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "sssssissssss",
        $cnpj, $nota_fiscal, $razao_social, $data_envio_expedicao, $data_envio_cliente,
        $quantidade, $codigo_rastreio_envio, $operacao_origem, $operacao_destino,
        $setor, $operador, $observacoes, $nota_fiscal_retorno
    );
}

// Executa a query
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["error" => "Erro ao salvar: " . $stmt->error]);
    exit();
}

// Fecha conexões
$stmt_check->close();
$stmt->close();
$conn->close();

// Verifica saída indesejada
$saidaIndesejada = ob_get_clean();

if (!empty(trim($saidaIndesejada))) {
    echo json_encode([
        "success" => false,
        "error" => "Saída inesperada detectada: " . trim($saidaIndesejada)
    ]);
} else {
    echo json_encode([
        "success" => true,
        "message" => "Cadastro/atualização concluída com sucesso.",
        "redirect" => "https://kpi.stbextrema.com.br/BackEnd/cadastro_realizado.php"
    ]);
}
exit();
?>