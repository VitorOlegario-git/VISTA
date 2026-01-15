<?php
ob_start();

// Ativar exibição de erros para debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/helpers.php';

// Cabeçalhos para evitar cache e garantir JSON
header('Content-Type: application/json');
definirHeadersSeguranca();

// Verifica se o usuário está autenticado
if (!verificarSessao(false)) {
    jsonError("Usuário não autenticado", 401);
}

require_once dirname(__DIR__) . '/conexao.php';


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // Dados principais
    $cnpj = sanitizeInput($_POST['cnpj'] ?? '');
    $nota_fiscal = sanitizeInput($_POST['nota_fiscal'] ?? '');
    $razao_social = sanitizeInput($_POST['razao_social'] ?? '');
    $quantidade = (int)($_POST['quantidade'] ?? 0);
    
    // Datas
    $data_inicio_analise = isset($_POST['data_inicio_analise']) ? trim($_POST['data_inicio_analise']) : '';
    $data_envio_orcamento = isset($_POST['data_envio_orcamento']) ? trim($_POST['data_envio_orcamento']) : '';
    $data_envio_orcamento = $data_envio_orcamento === '' ? null : $data_envio_orcamento;
  
    // Orçamento
    $numero_orcamento = sanitizeInput($_POST['numero_orcamento'] ?? null);
    $valor_orcamento = isset($_POST['valor_orcamento']) ? str_replace(',', '.', trim($_POST['valor_orcamento'])) : null;
    
    // Operações
    $operacao_origem = sanitizeInput($_POST['operacao_origem'] ?? '');
    $operacao_destino = sanitizeInput($_POST['operacao_destino'] ?? '');
    $setor = sanitizeInput($_POST['setor'] ?? '');
    $operador = sanitizeInput($_SESSION['username']);
    
    // Controle parcial
    $op_parcial = sanitizeInput($_POST['sim_nao'] ?? 'nao');
    $quantidade_parcial = isset($_POST['quantidade_parcial']) && $_POST['quantidade_parcial'] !== '' ? (int)$_POST['quantidade_parcial'] : null;
    
    // Outros
    $observacoes = sanitizeInput($_POST['obs'] ?? '');
    $acao = sanitizeInput($_POST['acao'] ?? 'inicio');


    // Validação básica
    if ($acao === 'inicio') {
    if (empty($cnpj) || empty($nota_fiscal) || empty($razao_social) || $quantidade <= 0 || empty($operacao_origem) || empty($operacao_destino)) {
        http_response_code(400);
        echo json_encode(["error" => "Todos os campos obrigatórios devem ser preenchidos corretamente."]);
        exit();
    }
}


    // Verifica se já existe um registro na analise_resumo
    $stmt_check_resumo = $conn->prepare("
        SELECT quantidade_total, quantidade_analisada, status 
        FROM analise_resumo 
        WHERE cnpj = ? AND nota_fiscal = ?
    ");
    $stmt_check_resumo->bind_param("ss", $cnpj, $nota_fiscal);
    $stmt_check_resumo->execute();
    $result = $stmt_check_resumo->get_result();
    $row_resumo = $result->fetch_assoc();

    if ($row_resumo) {
        $quantidade_total = (int)$row_resumo['quantidade_total'];
        $quantidade_analisada = (int)$row_resumo['quantidade_analisada'];
        $status_atual = $row_resumo['status'];
    } else {
        // Insere novo resumo
        $stmt_insert_resumo = $conn->prepare("
            INSERT INTO analise_resumo (
                cnpj, nota_fiscal, razao_social, quantidade_total, quantidade_analisada, 
                status, numero_orcamento, valor_orcamento, setor
            ) VALUES (?, ?, ?, ?, 0, 'envio_analise', ?, ?, ?)
        ");
        $stmt_insert_resumo->bind_param("sssisds", $cnpj, $nota_fiscal, $razao_social, $quantidade, $numero_orcamento, $valor_orcamento, $setor);
        $stmt_insert_resumo->execute();
        $stmt_insert_resumo->close();

        $quantidade_total = $quantidade;
        $quantidade_analisada = 0;
        $status_atual = 'envio_analise';
    }

    // Controle de parcialidade
    if ($op_parcial === 'nao') {
        if ($quantidade_analisada > 0) {
            echo json_encode([
                "error" => "Esta remessa já possui {$quantidade_analisada} equipamento(s) analisado(s). Para analisar o restante, use 'Análise Parcial' e informe a quantidade."
            ]);
            exit();
        }
        $quantidade_parcial = $quantidade;
    }

    if ($acao === 'inicio') {
        $quantidade_restante = $quantidade_total - $quantidade_analisada;
        if (!is_null($quantidade_parcial) && $quantidade_parcial > $quantidade_restante) {
            echo json_encode([
                "error" => "Quantidade parcial informada ($quantidade_parcial) excede o restante disponível ($quantidade_restante)."
            ]);
            exit();
        }

        $novo_total_analisado = $quantidade_analisada + ($quantidade_parcial ?? 0);

        $stmt_insert_parcial = $conn->prepare("
            INSERT INTO analise_parcial (
                cnpj, nota_fiscal, data_inicio_analise, data_envio_orcamento, razao_social,
                quantidade_total, quantidade_parcial, operador, operacao_origem, operacao_destino,
                observacoes, numero_orcamento, valor_orcamento, setor
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt_insert_parcial->bind_param(
            "sssssiisssssds",
            $cnpj, $nota_fiscal, $data_inicio_analise, $data_envio_orcamento, $razao_social,
            $quantidade_total, $quantidade_parcial, $operador, $operacao_origem, $operacao_destino,
            $observacoes, $numero_orcamento, $valor_orcamento, $setor
        );
        $stmt_insert_parcial->execute();
        $stmt_insert_parcial->close();

        $stmt_update_resumo = $conn->prepare("
            UPDATE analise_resumo SET quantidade_analisada = ?, numero_orcamento = ?, valor_orcamento = ?
            WHERE cnpj = ? AND nota_fiscal = ?
        ");
        $stmt_update_resumo->bind_param("isdss", $novo_total_analisado, $numero_orcamento, $valor_orcamento, $cnpj, $nota_fiscal);
        $stmt_update_resumo->execute();
        $stmt_update_resumo->close();

        if ($status_atual === 'envio_analise' && $novo_total_analisado >= $quantidade_total) {
            $stmt_status = $conn->prepare("
                UPDATE analise_resumo SET status = 'em_analise' 
                WHERE cnpj = ? AND nota_fiscal = ?
            ");
            $stmt_status->bind_param("ss", $cnpj, $nota_fiscal);
            $stmt_status->execute();
            $stmt_status->close();
        }

    } elseif ($acao === 'fim') {
        $stmt_update = $conn->prepare("
            UPDATE analise_parcial 
            SET operacao_destino = ?, observacoes = ?, data_envio_orcamento = ?, numero_orcamento = ?, valor_orcamento = ?
            WHERE cnpj = ? AND nota_fiscal = ? AND data_inicio_analise = ? AND operador = ? 
            AND operacao_destino = 'em_analise' 
            ORDER BY id DESC LIMIT 1
        ");
        $stmt_update->bind_param(
            "ssssdssss",
            $operacao_destino, $observacoes, $data_envio_orcamento, $numero_orcamento, $valor_orcamento,
            $cnpj, $nota_fiscal, $data_inicio_analise, $operador
        );
        $stmt_update->execute();

        if ($stmt_update->affected_rows === 0) {
            echo json_encode(["error" => "Nenhuma análise em andamento encontrada."]);
            exit();
        }

        $stmt_update->close();

        $stmt_update_resumo = $conn->prepare("
            UPDATE analise_resumo SET numero_orcamento = ?, valor_orcamento = ?
            WHERE cnpj = ? AND nota_fiscal = ?
        ");
        $stmt_update_resumo->bind_param("sdss", $numero_orcamento, $valor_orcamento, $cnpj, $nota_fiscal);
        $stmt_update_resumo->execute();
        $stmt_update_resumo->close();
    }

    // Verifica se todas as análises foram finalizadas
    $stmt_total = $conn->prepare("
        SELECT SUM(quantidade_parcial) AS total FROM analise_parcial 
        WHERE cnpj = ? AND nota_fiscal = ? AND operacao_destino != 'em_analise'
    ");
    $stmt_total->bind_param("ss", $cnpj, $nota_fiscal);
    $stmt_total->execute();
    $resultado = $stmt_total->get_result();
    $total_concluido = (int)($resultado->fetch_assoc()['total'] ?? 0);
    $stmt_total->close();

    if ($total_concluido >= $quantidade_total) {
        $stmt_finaliza = $conn->prepare("
            UPDATE analise_resumo SET status = 'aguardando_pg' 
            WHERE cnpj = ? AND nota_fiscal = ?
        ");
        $stmt_finaliza->bind_param("ss", $cnpj, $nota_fiscal);
        $stmt_finaliza->execute();
        $stmt_finaliza->close();
    }

    $stmt_check_resumo->close();
    $conn->close();

    echo json_encode([
        "success" => "Cadastro realizado com sucesso.",
        "acao" => $acao
    ]);
    exit();
}

echo json_encode(["error" => "Requisição inválida."]);
exit();
