<?php 
session_start();

header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

$tempo_limite = 1200; // 20 minutos

// Verifica inatividade
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $tempo_limite) {
    session_unset();
    session_destroy();
    header("Location: /localhost/FrontEnd/tela_login.php");
    exit();
}

// Verifica se a sessão está ativa
if (!isset($_SESSION['username'])) {
    header("Location: /localhost/FrontEnd/tela_login.php");
    exit();
}

$_SESSION['last_activity'] = time();


require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";


function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e sanitiza os dados do formulário
    $cod_rastreio = sanitizeInput($_POST['cod_rastreio']);
    $cnpj = sanitizeInput($_POST['cnpj']);
    $data_recebimento = trim($_POST['data_recebimento']);
    $data_envio_analise = !empty($_POST['data_envio_analise']) ? trim($_POST['data_envio_analise']) : NULL;
    $razao_social = !empty($_POST['razao_social']) ? sanitizeInput($_POST['razao_social']) : "";
    $setor = sanitizeInput($_POST['setor']);
    $nota_fiscal = isset($_POST['nota_fiscal']) ? trim($_POST['nota_fiscal']) : "";
    $quantidade = isset($_POST['quantidade']) && $_POST['quantidade'] !== "" ? intval($_POST['quantidade']) : 0;
    $operacao_origem = isset($_POST['operacao_origem']) && $_POST['operacao_origem'] !== "" ? sanitizeInput($_POST['operacao_origem']) : NULL;
    $operacao_destino = sanitizeInput($_POST['operacao_destino']);
    $operador = sanitizeInput($_POST['operador']);
    $obs = !empty($_POST['obs']) ? sanitizeInput($_POST['obs']) : NULL;

    // Verifica se os campos obrigatórios estão preenchidos corretamente
    if (
        empty($cod_rastreio) || empty($cnpj) || empty($data_recebimento) || empty($razao_social) ||
        empty($setor) || $quantidade <= 0 || empty($operacao_origem) ||
        empty($operacao_destino) || empty($operador)
    ) {
        http_response_code(400);
        echo json_encode(["error" => "Todos os campos obrigatórios devem ser preenchidos corretamente!"]);
        exit;
    }

    // Verifica se o código de rastreio já existe na base de dados
    $stmt_check = $conn->prepare("SELECT id FROM recebimentos WHERE cod_rastreio = ?");
    $stmt_check->bind_param("s", $cod_rastreio);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result();

    if ($check_result->num_rows > 0) {
        // Atualiza os dados existentes
        $stmt_update = $conn->prepare("
            UPDATE recebimentos 
            SET cnpj = ?, data_recebimento = ?, data_envio_analise = ?, razao_social = ?, setor = ?, 
                nota_fiscal = ?, quantidade = ?, operacao_origem = ?, operacao_destino = ?, 
                operador = ?, observacoes = ? 
            WHERE cod_rastreio = ?
        ");

        $stmt_update->bind_param(
            "ssssssisssss",
            $cnpj, 
            $data_recebimento, 
            $data_envio_analise,
            $razao_social, 
            $setor,
            $nota_fiscal, 
            $quantidade, 
            $operacao_origem,  
            $operacao_destino, 
            $operador, 
            $obs, 
            $cod_rastreio
        );

        if (!$stmt_update->execute()) {
            http_response_code(500);
            echo json_encode(["error" => "Erro ao atualizar: " . $stmt_update->error]);
            exit;
        }
        $cadastro_sucesso = true; // ✅ ADICIONE ISSO AQUI
        $stmt_update->close();
        
    } else {
        // Insere um novo registro
        $stmt_insert = $conn->prepare("
            INSERT INTO recebimentos (
                cod_rastreio, cnpj, data_recebimento, data_envio_analise, razao_social, setor, nota_fiscal, 
                quantidade, operacao_origem, operacao_destino, operador, observacoes
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt_insert->bind_param(
            "sssssssissss",
            $cod_rastreio, 
            $cnpj, 
            $data_recebimento, 
            $data_envio_analise,
            $razao_social, 
            $setor,
            $nota_fiscal, 
            $quantidade, 
            $operacao_origem, 
            $operacao_destino, 
            $operador, 
            $obs
        );

        if (!$stmt_insert->execute()) {
            http_response_code(500);
            echo json_encode(["error" => "Erro ao cadastrar: " . $stmt_insert->error]);
            exit;
        }
        $cadastro_sucesso = true; 
        $stmt_insert->close();
    }
    if ($cadastro_sucesso) {
        header("Location: /localhost/BackEnd/cadastro_realizado.php");
        exit();
    } else {
        echo "Erro ao cadastrar!";
    }
    // Fecha conexões
    $stmt_check->close();
    $conn->close();
}
?>
