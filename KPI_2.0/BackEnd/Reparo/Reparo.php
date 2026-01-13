<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

$tempo_limite = 1200;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $tempo_limite) {
    session_unset();
    session_destroy();
    header("Location: https://kpi.stbextrema.com.br/FrontEnd/tela_login.php");
    exit();
}

if (!isset($_SESSION['username'])) {
    header("Location: https://kpi.stbextrema.com.br/FrontEnd/tela_login.php");
    exit();
}

$_SESSION['last_activity'] = time();

require_once dirname(__DIR__) . '/conexao.php';

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

    // Validação detalhada
    $errors = [];
    if (empty($cnpj)) $errors[] = "CNPJ é obrigatório.";
    if ($quantidade <= 0) $errors[] = "Quantidade deve ser maior que zero.";
    if (empty($operacao_origem)) $errors[] = "Operação de origem é obrigatória.";
    if (empty($operacao_destino)) $errors[] = "Operação de destino é obrigatória.";
    if ($valor_orcamento !== null && !is_numeric($valor_orcamento)) $errors[] = "O valor do orçamento deve ser numérico.";
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(["error" => implode(" ", $errors)]);
        exit();
    }

    // Buscar ou criar reparo_resumo
    $stmt_check_resumo = $conn->prepare("SELECT quantidade_total, quantidade_reparada, status, numero_orcamento, valor_orcamento, setor, segregado FROM reparo_resumo WHERE cnpj = ? AND nota_fiscal = ?");
    $stmt_check_resumo->bind_param("ss", $cnpj, $nota_fiscal);
    if (!$stmt_check_resumo->execute()) {
        http_response_code(500);
        echo json_encode(["error" => "Erro ao consultar reparo_resumo: " . $stmt_check_resumo->error]);
        exit();
    }
    $result_check = $stmt_check_resumo->get_result();
    $row_resumo = $result_check->fetch_assoc();

    if ($row_resumo) {
        $quantidade_total = (int)$row_resumo['quantidade_total'];
        $quantidade_reparada = (int)$row_resumo['quantidade_reparada'];
        $status_atual = strtolower(trim($row_resumo['status']));
        $numero_orcamento_existente = $row_resumo['numero_orcamento'];
        $valor_orcamento_existente = $row_resumo['valor_orcamento'];
        $setor = $row_resumo['setor'];
        $segregado_resumo = (int)$row_resumo['segregado'];
    } else {
        $stmt_insert_resumo = $conn->prepare("INSERT INTO reparo_resumo (cnpj, nota_fiscal, razao_social, quantidade_total, quantidade_reparada, status, numero_orcamento, valor_orcamento, setor, segregado) VALUES (?, ?, ?, ?, 0, 'aguardando_pg', ?, ?, ?, 0)");
        $stmt_insert_resumo->bind_param("sssisdss", $cnpj, $nota_fiscal, $razao_social, $quantidade, $numero_orcamento, $valor_orcamento, $setor);
        if (!$stmt_insert_resumo->execute()) {
            http_response_code(500);
            echo json_encode(["error" => "Erro ao inserir reparo_resumo: " . $stmt_insert_resumo->error]);
            exit();
        }
        $stmt_insert_resumo->close();
        $quantidade_total = $quantidade;
        $quantidade_reparada = 0;
        $segregado_resumo = 0;
        $status_atual = 'aguardando_pg';
    }

    if ($op_parcial === 'nao') {
        if ($quantidade_reparada > 0) {
            http_response_code(400);
            echo json_encode(["error" => "Deixar a operação parcial em branco."]);
            exit();
        }
        $quantidade_parcial = $quantidade;
    }

    switch ($acao) {
        case 'inicio':
        case 'fim':
            $ehSegregado = ($operacao_destino === 'segregado');
            $valor_parcial = $ehSegregado ? 0 : $quantidade_parcial;
            $valor_segregado = $ehSegregado ? $quantidade_parcial : 0;

            // Buscar último apontamento
            $stmt_check_parcial = $conn->prepare("
                SELECT id, quantidade_parcial, segregado 
                FROM reparo_parcial 
                WHERE cnpj = ? AND nota_fiscal = ? AND data_inicio_reparo = ? AND operador = ? 
                ORDER BY id DESC 
                LIMIT 1
            ");
            $stmt_check_parcial->bind_param("ssss", $cnpj, $nota_fiscal, $data_inicio_reparo, $operador);
            if (!$stmt_check_parcial->execute()) {
                http_response_code(500);
                echo json_encode(["error" => "Erro ao consultar reparo_parcial: " . $stmt_check_parcial->error]);
                exit();
            }
            $result_parcial = $stmt_check_parcial->get_result();
            $row_parcial = $result_parcial->fetch_assoc();
            $stmt_check_parcial->close();

            if ($row_parcial) {
                $id_existente = $row_parcial['id'];
                $novo_valor_parcial = $row_parcial['quantidade_parcial'] + $valor_parcial;
                $novo_valor_segregado = $row_parcial['segregado'] + $valor_segregado;

                $stmt_update_parcial = $conn->prepare("UPDATE reparo_parcial SET quantidade_parcial = ?, segregado = ?, observacoes = ?, numero_orcamento = ?, valor_orcamento = ?, operacao_origem = ?, operacao_destino = ?, setor = ?, data_solicitacao_nf = ?, data_registro = NOW() WHERE id = ?");
                $stmt_update_parcial->bind_param("ddsssssssi", $novo_valor_parcial, $novo_valor_segregado, $observacoes, $numero_orcamento, $valor_orcamento, $operacao_origem, $operacao_destino, $setor, $data_solicitacao_nf, $id_existente);
                if (!$stmt_update_parcial->execute()) {
                    http_response_code(500);
                    echo json_encode(["error" => "Erro ao atualizar reparo_parcial: " . $stmt_update_parcial->error]);
                    exit();
                }
                $stmt_update_parcial->close();
            } else {
                $quantidade_total_final = $ehSegregado ? $quantidade_total - $quantidade_parcial : $quantidade_total;
                $stmt_insert_parcial = $conn->prepare("INSERT INTO reparo_parcial (cnpj, nota_fiscal, data_inicio_reparo, data_solicitacao_nf, razao_social, quantidade_total, quantidade_parcial, segregado, operador, operacao_origem, operacao_destino, observacoes, numero_orcamento, valor_orcamento, setor) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_insert_parcial->bind_param("sssssiiisssssds", $cnpj, $nota_fiscal, $data_inicio_reparo, $data_solicitacao_nf, $razao_social, $quantidade_total_final, $valor_parcial, $valor_segregado, $operador, $operacao_origem, $operacao_destino, $observacoes, $numero_orcamento, $valor_orcamento, $setor);
                if (!$stmt_insert_parcial->execute()) {
                    http_response_code(500);
                    echo json_encode(["error" => "Erro ao inserir reparo_parcial: " . $stmt_insert_parcial->error]);
                    exit();
                }
                $stmt_insert_parcial->close();
            }

            // Atualizar reparo_resumo (quantidade_total e segregado)
            if ($acao === 'inicio' && $ehSegregado) {
                $novo_total = $quantidade_total - $valor_segregado;
                $novo_segregado = $segregado_resumo + $valor_segregado;
                $stmt_subtrai_segregado = $conn->prepare("UPDATE reparo_resumo SET quantidade_total = ?, segregado = ? WHERE cnpj = ? AND nota_fiscal = ?");
                $stmt_subtrai_segregado->bind_param("iiss", $novo_total, $novo_segregado, $cnpj, $nota_fiscal);
                if (!$stmt_subtrai_segregado->execute()) {
                    http_response_code(500);
                    echo json_encode(["error" => "Erro ao atualizar reparo_resumo (segregado): " . $stmt_subtrai_segregado->error]);
                    exit();
                }
                $stmt_subtrai_segregado->close();
                $quantidade_total = $novo_total;
            }

            // Buscar somatórios
            $stmt_final = $conn->prepare("
                SELECT 
                    SUM(CASE WHEN operacao_destino = 'em_reparo' THEN quantidade_parcial ELSE 0 END) AS total_reparado,
                    SUM(CASE WHEN operacao_destino = 'devolucao' THEN quantidade_parcial ELSE 0 END) AS total_devolvido
                FROM reparo_parcial
                WHERE cnpj = ? AND nota_fiscal = ?
            ");
            $stmt_final->bind_param("ss", $cnpj, $nota_fiscal);
            if (!$stmt_final->execute()) {
                http_response_code(500);
                echo json_encode(["error" => "Erro ao consultar somatórios: " . $stmt_final->error]);
                exit();
            }
            $result_final = $stmt_final->get_result();
            $dados_final = $result_final->fetch_assoc();
            $stmt_final->close();

            $total_reparado = (int)($dados_final['total_reparado'] ?? 0);
            $total_devolvido = (int)($dados_final['total_devolvido'] ?? 0);
            $total_processado = $total_reparado + $total_devolvido;

            // Definição do novo status
            $novo_status = $status_atual;
            if ($acao === 'inicio' && !$ehSegregado && $total_reparado >= $quantidade_total) {
                $novo_status = 'em_reparo';
            } elseif ($acao === 'fim' && !$ehSegregado) {
                $stmt_ultima_operacao = $conn->prepare("
                    SELECT operacao_destino 
                    FROM reparo_parcial 
                    WHERE cnpj = ? AND nota_fiscal = ? 
                    ORDER BY id DESC 
                    LIMIT 1
                ");
                $stmt_ultima_operacao->bind_param("ss", $cnpj, $nota_fiscal);
                if (!$stmt_ultima_operacao->execute()) {
                    http_response_code(500);
                    echo json_encode(["error" => "Erro ao consultar última operação: " . $stmt_ultima_operacao->error]);
                    exit();
                }
                $result_ultima = $stmt_ultima_operacao->get_result();
                $ultima_operacao = $result_ultima->fetch_assoc()['operacao_destino'] ?? null;
                $stmt_ultima_operacao->close();

                if ($ultima_operacao === null) {
                    http_response_code(400);
                    echo json_encode(["error" => "Nenhuma operação parcial encontrada para definir o status."]);
                    exit();
                }

                $novo_status = ($ultima_operacao === 'devolucao') ? 'estocado' : ($ultima_operacao === 'em_reparo' ? 'aguardando_nf' : $ultima_operacao);
                error_log("✅ Definido novo_status: $novo_status com base em operacao_destino: $ultima_operacao");
            }

            // Atualiza reparo_resumo
            $stmt_update_resumo = $conn->prepare("UPDATE reparo_resumo SET quantidade_reparada = ?, numero_orcamento = ?, valor_orcamento = ?, status = ? WHERE cnpj = ? AND nota_fiscal = ?");
            $stmt_update_resumo->bind_param("isdsss", $total_reparado, $numero_orcamento, $valor_orcamento, $novo_status, $cnpj, $nota_fiscal);
            if (!$stmt_update_resumo->execute()) {
                error_log("❌ Erro ao atualizar reparo_resumo: " . $stmt_update_resumo->error);
                http_response_code(500);
                echo json_encode(["error" => "Erro ao atualizar reparo_resumo: " . $stmt_update_resumo->error]);
                exit();
            }
            error_log("✅ Atualização executada. Status novo: $novo_status, CNPJ: $cnpj, NF: $nota_fiscal");
            if ($stmt_update_resumo->affected_rows === 0) {
                error_log("⚠️ Nenhuma linha foi alterada — status pode já estar igual ou CNPJ/NF não casaram.");
            }
            $stmt_update_resumo->close();
            break;
    }

    $stmt_check_resumo->close();

    echo json_encode([
        "success" => "Reparo registrado com sucesso!",
        "acao" => $acao,
        "status" => $novo_status,
        "quantidade_total" => $quantidade_total,
        "quantidade_reparada" => $total_reparado,
        "quantidade_devolvida" => $total_devolvido
    ]);
}

$conn->close();
ob_end_flush();
?>