<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false); // Compatibilidade adicional
header("Pragma: no-cache"); // Compatível com HTTP/1.0
header("Expires: 0"); // Expira imediatamente

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

// Função de sanitização de entrada
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $cnpj = sanitizeInput($_POST['cnpj'] ?? '');
    $nota_fiscal = sanitizeInput($_POST['nota_fiscal'] ?? '');
    $data_inicio_reparo = trim($_POST['data_inicio_reparo'] ?? '');
    $data_solicitacao_nf = !empty($_POST['data_solicitacao_nf']) ? trim($_POST['data_solicitacao_nf']) : null;
    $razao_social = sanitizeInput($_POST['razao_social'] ?? '');
    $quantidade = (int)($_POST['quantidade'] ?? 0);
    $op_parcial = sanitizeInput($_POST['sim_nao'] ?? 'nao');
    $quantidade_parcial = (int)($_POST['quantidade_parcial'] ?? 0);
    $numero_orcamento = sanitizeInput($_POST['numero_orcamento'] ?? null);
    $valor_orcamento = isset($_POST['valor_orcamento']) ? str_replace(',', '.', trim($_POST['valor_orcamento'])) : null;
    $operacao_origem = sanitizeInput($_POST['operacao_origem'] ?? '');
    $operacao_destino = sanitizeInput($_POST['operacao_destino'] ?? '');
    $setor = sanitizeInput($_POST['setor'] ?? '');
    $operador = sanitizeInput($_SESSION['username']);
    $observacoes = sanitizeInput($_POST['obs'] ?? '');
    $acao = sanitizeInput($_POST['acao'] ?? 'inicio');

    // Validação de campos obrigatórios
    if (empty($cnpj) || $quantidade <= 0 || empty($operacao_origem) || empty($operacao_destino)) {
        http_response_code(400);
        echo json_encode(["error" => "Todos os campos obrigatórios devem ser preenchidos corretamente."]);
        exit();
    }

    // Consulta se já existe resumo
    $stmt_check_resumo = $conn->prepare("
        SELECT quantidade_total, quantidade_reparada, status, numero_orcamento, valor_orcamento, setor 
        FROM reparo_resumo 
        WHERE cnpj = ? AND nota_fiscal = ?
    ");
    $stmt_check_resumo->bind_param("ss", $cnpj, $nota_fiscal);
    $stmt_check_resumo->execute();
    $result_check = $stmt_check_resumo->get_result();
    $row_resumo = $result_check->fetch_assoc();

    if ($row_resumo) {
        $quantidade_total = (int)$row_resumo['quantidade_total'];
        $quantidade_reparada = (int)$row_resumo['quantidade_reparada'];
        $status_atual = strtolower(trim($row_resumo['status']));
        $numero_orcamento_existente = $row_resumo['numero_orcamento'];
        $valor_orcamento_existente = $row_resumo['valor_orcamento'];
        $setor = $row_resumo['setor'];
    } else {
        $stmt_insert_resumo = $conn->prepare("
            INSERT INTO reparo_resumo (cnpj, nota_fiscal, razao_social, quantidade_total, quantidade_reparada, status, numero_orcamento, valor_orcamento, setor) 
            VALUES (?, ?, ?, ?, 0, 'aguardando_pg', ?, ?, ?)
        ");
        $stmt_insert_resumo->bind_param("sssisds", $cnpj, $nota_fiscal, $razao_social, $quantidade, $numero_orcamento, $valor_orcamento, $setor);
        $stmt_insert_resumo->execute();
        $stmt_insert_resumo->close();

        $quantidade_total = $quantidade;
        $quantidade_reparada = 0;
        $status_atual = 'aguardando_pg';
    }

    // Controle de parcialidade
    if ($op_parcial === 'nao') {
        if ($quantidade_reparada > 0) {
            http_response_code(400);
            echo json_encode(["error" => "Deixar a operação parcial em branco."]);
            exit();
        }
        $quantidade_parcial = $quantidade;
    }

    if ($acao === 'inicio') {
        $quantidade_restante = $quantidade_total - $quantidade_reparada;
        if ($quantidade_parcial > $quantidade_restante) {
            http_response_code(400);
            echo json_encode(["error" => "Quantidade parcial ($quantidade_parcial) excede o restante disponível ($quantidade_restante)."]);
            exit();
        }

        $novo_total_reparado = $quantidade_reparada + $quantidade_parcial;

        // Verifica se já existe apontamento parcial aberto
        $stmt_check_parcial = $conn->prepare("
            SELECT id, quantidade_parcial 
            FROM reparo_parcial 
            WHERE cnpj = ? AND nota_fiscal = ? AND data_inicio_reparo = ? AND operador = ? AND operacao_destino = 'em_reparo'
            ORDER BY id DESC LIMIT 1
        ");
        $stmt_check_parcial->bind_param("ssss", $cnpj, $nota_fiscal, $data_inicio_reparo, $operador);
        $stmt_check_parcial->execute();
        $result_parcial = $stmt_check_parcial->get_result();
        $row_parcial = $result_parcial->fetch_assoc();

        if ($row_parcial) {
            $novo_valor = $row_parcial['quantidade_parcial'] + $quantidade_parcial;
            $id_existente = $row_parcial['id'];

            $stmt_update_parcial = $conn->prepare("
                UPDATE reparo_parcial 
                SET quantidade_parcial = ?, observacoes = ?, numero_orcamento = ?, valor_orcamento = ?, operacao_origem = ?, setor = ?, data_solicitacao_nf = ?
                WHERE id = ?
            ");
            $stmt_update_parcial->bind_param("dsssissi", $novo_valor, $observacoes, $numero_orcamento, $valor_orcamento, $operacao_origem, $setor, $data_solicitacao_nf, $id_existente);
            $stmt_update_parcial->execute();
            $stmt_update_parcial->close();
        } else {
            $stmt_insert_parcial = $conn->prepare("
                INSERT INTO reparo_parcial (cnpj, nota_fiscal, data_inicio_reparo, data_solicitacao_nf, razao_social, quantidade_total, quantidade_parcial, operador, operacao_origem, operacao_destino, observacoes, numero_orcamento, valor_orcamento, setor) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt_insert_parcial->bind_param("sssssiisssssds", $cnpj, $nota_fiscal, $data_inicio_reparo, $data_solicitacao_nf, $razao_social, $quantidade_total, $quantidade_parcial, $operador, $operacao_origem, $operacao_destino, $observacoes, $numero_orcamento, $valor_orcamento, $setor);
            $stmt_insert_parcial->execute();
            $stmt_insert_parcial->close();
        }
        $stmt_check_parcial->close();

        $novo_status = ($operacao_destino === 'em_reparo') ? 'em_reparo' : $status_atual;

        $stmt_update_resumo = $conn->prepare("
            UPDATE reparo_resumo 
            SET quantidade_reparada = ?, numero_orcamento = ?, valor_orcamento = ?, status = ? 
            WHERE cnpj = ? AND nota_fiscal = ?
        ");
        $stmt_update_resumo->bind_param("isdsss", $novo_total_reparado, $numero_orcamento, $valor_orcamento, $novo_status, $cnpj, $nota_fiscal);
        $stmt_update_resumo->execute();
        $stmt_update_resumo->close();

    } elseif ($acao === 'fim') {
        $stmt_update_parcial = $conn->prepare("
            UPDATE reparo_parcial 
            SET operacao_origem = ?, operacao_destino = ?, observacoes = ?, data_solicitacao_nf = ?, numero_orcamento = ?, valor_orcamento = ?
            WHERE cnpj = ? AND nota_fiscal = ? AND data_inicio_reparo = ? AND operador = ?
            ORDER BY id DESC LIMIT 1
        ");
        $stmt_update_parcial->bind_param("ssssdsssss", $operacao_origem, $operacao_destino, $observacoes, $data_solicitacao_nf, $numero_orcamento, $valor_orcamento, $cnpj, $nota_fiscal, $data_inicio_reparo, $operador);
        $stmt_update_parcial->execute();
        if ($stmt_update_parcial->affected_rows === 0) {
            http_response_code(400);
            echo json_encode(["error" => "Nenhum reparo em andamento encontrado."]);
            exit();
        }
        $stmt_update_parcial->close();

        $stmt_update_resumo = $conn->prepare("
            UPDATE reparo_resumo 
            SET numero_orcamento = ?, valor_orcamento = ?, status = 'aguardando_NF_retorno'
            WHERE cnpj = ? AND nota_fiscal = ? AND status = 'em_reparo'
        ");
        $stmt_update_resumo->bind_param("sdss", $numero_orcamento, $valor_orcamento, $cnpj, $nota_fiscal);
        $stmt_update_resumo->execute();
        $stmt_update_resumo->close();
    }

    $stmt_total_concluido = $conn->prepare("
        SELECT SUM(quantidade_parcial) AS total FROM reparo_parcial 
        WHERE cnpj = ? AND nota_fiscal = ? AND operacao_destino != 'em_reparo'
    ");
    $stmt_total_concluido->bind_param("ss", $cnpj, $nota_fiscal);
    $stmt_total_concluido->execute();
    $result_total = $stmt_total_concluido->get_result();
    $total_concluido = (int)($result_total->fetch_assoc()['total']);
    $stmt_total_concluido->close();

    if ($total_concluido >= $quantidade_total) {
        $stmt_finaliza_status = $conn->prepare("
            UPDATE reparo_resumo SET status = 'aguardando_NF_retorno' WHERE cnpj = ? AND nota_fiscal = ?
        ");
        $stmt_finaliza_status->bind_param("ss", $cnpj, $nota_fiscal);
        $stmt_finaliza_status->execute();
        $stmt_finaliza_status->close();
    }

    $stmt_check_resumo->close();
    $conn->close();

    echo json_encode(["success" => "Reparo registrado com sucesso!", "acao" => $acao]);
    exit();

}

ob_end_flush();
?>
