<?php 
// Ativar exibição de erros para debug (REMOVER EM PRODUÇÃO)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Usar __DIR__ ao invés de DOCUMENT_ROOT
require_once dirname(__DIR__) . '/helpers.php';

// Verifica sessão sem redirecionamento e retorna 401 JSON se não autenticado
if (!verificarSessao(false)) {
    jsonUnauthorized();
    exit;
}
definirHeadersSeguranca();

require_once dirname(__DIR__) . '/conexao.php';

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
        // Retornar JSON com redirect em vez de header Location
        header('Content-Type: application/json');
        echo json_encode([
            "success" => true,
            "message" => "Recebimento cadastrado com sucesso",
            // route name (sem /) para ser usado pela função redirectTo() no frontend
            "redirect" => "cadastro-realizado"
        ]);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(["error" => "Erro ao cadastrar!"]);
    }
    // Fecha conexões
    $stmt_check->close();
    $conn->close();
}
?>
