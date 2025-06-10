<?php
ob_start(); // Inicia o buffer de saída

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
header("Content-Type: application/json");

$tempo_limite = 1200; // 20 minutos

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $tempo_limite) {
    session_unset();
    session_destroy();
    echo json_encode(["success" => false, "error" => "Sessão expirada."]);
    exit();
}

if (!isset($_SESSION['username'])) {
    echo json_encode(["success" => false, "error" => "Sessão não iniciada."]);
    exit();
}

$_SESSION['last_activity'] = time();

// Conexão
require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

// Sanitização
function limpar($valor) {
    return htmlspecialchars(strip_tags(trim($valor)), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cnpj = limpar($_POST['cnpj'] ?? '');
    $nota_fiscal = limpar($_POST['nota_fiscal'] ?? '');
    $data_inicio_qualidade = trim($_POST['data_inicio_qualidade'] ?? '');
    $data_envio_expedicao = !empty($_POST['data_envio_expedicao']) ? trim($_POST['data_envio_expedicao']) : null;
    $razao_social = limpar($_POST['razao_social'] ?? '');
    $quantidade = (int)($_POST['quantidade'] ?? 0);
    $quantidade_parcial = (int)($_POST['quantidade_parcial'] ?? 0);
    $operacao_origem = limpar($_POST['operacao_origem'] ?? '');
    $operacao_destino = limpar($_POST['operacao_destino'] ?? '');
    $setor = limpar($_POST['setor'] ?? '');
    $operador = limpar($_SESSION['username'] ?? 'Desconhecido');
    $observacoes = limpar($_POST['obs'] ?? '');
    $nota_fiscal_retorno = limpar($_POST['nota_fiscal_retorno'] ?? '');

    if (
        empty($cnpj) || empty($nota_fiscal) || empty($razao_social) ||
        empty($operacao_origem) || empty($operacao_destino) || empty($setor)
    ) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Todos os campos obrigatórios devem ser preenchidos."]);
        exit();
    }

    $sql = "UPDATE qualidade_registro SET 
                razao_social = ?, 
                data_inicio_qualidade = ?, 
                data_envio_expedicao = ?, 
                quantidade = ?, 
                quantidade_parcial = ?, 
                operacao_origem = ?, 
                operacao_destino = ?, 
                setor = ?, 
                operador = ?, 
                observacoes = ?, 
                nota_fiscal_retorno = ?
            WHERE cnpj = ? AND nota_fiscal = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["success" => false, "error" => "Erro na preparação da query: " . $conn->error]);
        exit();
    }

    $stmt->bind_param(
        "sssiissssssss",
        $razao_social, $data_inicio_qualidade, $data_envio_expedicao, $quantidade,
        $quantidade_parcial, $operacao_origem, $operacao_destino, $setor,
        $operador, $observacoes, $nota_fiscal_retorno, $cnpj, $nota_fiscal
    );

    if (!$stmt->execute()) {
        echo json_encode(["success" => false, "error" => "Erro ao atualizar: " . $stmt->error]);
        $stmt->close();
        $conn->close();
        exit();
    }

    $stmt->close();
    $conn->close();

    $saidaIndesejada = ob_get_contents();
    ob_end_clean();

    if (!empty($saidaIndesejada)) {
        echo json_encode(["success" => false, "error" => "Saída inesperada: " . trim($saidaIndesejada)]);
    } else {
        echo json_encode([
            "success" => true,
            "message" => "Dados atualizados com sucesso.",
            "redirect" => "/localhost/BackEnd/cadastro_realizado.php"
        ]);
    }
    exit();
}

// Se não for POST
echo json_encode(["success" => false, "error" => "Método inválido. Apenas POST é aceito."]);
?>
