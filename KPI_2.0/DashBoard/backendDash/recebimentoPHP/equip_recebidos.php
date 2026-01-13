<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';

function getFiltroDatasEOperador(&$tipos, &$params) {
    $condicoes = [];

    if (!empty($_POST['data_inicial']) && !empty($_POST['data_final'])) {
        $data_inicio = str_replace("/", "-", $_POST['data_inicial']);
        $data_fim = str_replace("/", "-", $_POST['data_final']);

        if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_inicio) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_fim)) {
            $condicoes[] = "DATE(data_recebimento) BETWEEN ? AND ?";
            $tipos .= "ss";
            $params[] = $data_inicio;
            $params[] = $data_fim;
        }
    }

    if (!empty($_POST['operador'])) {
        $condicoes[] = "operador = ?";
        $tipos .= "s";
        $params[] = $_POST['operador'];
    }

    return !empty($condicoes) ? "WHERE " . implode(" AND ", $condicoes) : "";
}

$tipos = "";
$params = [];
$where = getFiltroDatasEOperador($tipos, $params);

// Consulta 1: Total geral
$sql_total = "SELECT SUM(quantidade) AS total_recebido FROM recebimentos $where";
$stmt_total = $conn->prepare($sql_total);
if (!empty($params)) $stmt_total->bind_param($tipos, ...$params);
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$total_recebido = $result_total->fetch_assoc()['total_recebido'] ?? 0;

// Consulta 2: Por setor
$sql_setor = "SELECT setor, SUM(quantidade) AS total_pecas FROM recebimentos $where GROUP BY setor ORDER BY total_pecas DESC";
$stmt_setor = $conn->prepare($sql_setor);
if (!empty($params)) $stmt_setor->bind_param($tipos, ...$params);
$stmt_setor->execute();
$result_setor = $stmt_setor->get_result();
$dados_setor = [];
while ($row = $result_setor->fetch_assoc()) {
    $dados_setor[] = [
        "setor" => $row['setor'],
        "total_pecas" => $row['total_pecas']
    ];
}

// Consulta 3: Por semana
$sql_semanal = "
    SELECT 
        semana_inicio,
        semana_fim,
        SUM(quantidade) AS total_recebido
    FROM (
        SELECT 
            DATE(data_recebimento) - INTERVAL WEEKDAY(data_recebimento) DAY AS semana_inicio,
            DATE(data_recebimento) + INTERVAL (6 - WEEKDAY(data_recebimento)) DAY AS semana_fim,
            quantidade
        FROM recebimentos
        $where
    ) AS sub
    GROUP BY semana_inicio, semana_fim
    ORDER BY semana_inicio
";

$stmt_semanal = $conn->prepare($sql_semanal);
if (!empty($params)) $stmt_semanal->bind_param($tipos, ...$params);
$stmt_semanal->execute();
$result_semanal = $stmt_semanal->get_result();
$dados_semanal = [];
while ($row = $result_semanal->fetch_assoc()) {
    $dados_semanal[] = [
        "inicio" => $row['semana_inicio'],
        "fim" => $row['semana_fim'],
        "total_recebido" => $row['total_recebido']
    ];
}

// Consulta 4: Por mês
$sql_mensal = "
    SELECT 
        mes_chave,
        DATE_FORMAT(STR_TO_DATE(CONCAT(mes_chave, '-01'), '%Y-%m-%d'), '%b %Y') AS mes_ano,
        SUM(quantidade) AS total_recebido
    FROM (
        SELECT 
            DATE_FORMAT(data_recebimento, '%Y-%m') AS mes_chave,
            quantidade
        FROM recebimentos
        $where
    ) AS sub
    GROUP BY mes_chave
    ORDER BY mes_chave
";

$stmt_mensal = $conn->prepare($sql_mensal);
if (!empty($params)) $stmt_mensal->bind_param($tipos, ...$params);
$stmt_mensal->execute();
$result_mensal = $stmt_mensal->get_result();
$dados_mensal = [];
while ($row = $result_mensal->fetch_assoc()) {
    $dados_mensal[] = [
        "mes" => $row['mes_ano'],
        "total_recebido" => $row['total_recebido']
    ];
}

// Retorna o JSON final
echo json_encode([
    "total_recebido" => $total_recebido,
    "dados" => $dados_setor,
    "semanal" => $dados_semanal,
    "mensal" => $dados_mensal
]);

$stmt_total->close();
$stmt_setor->close();
$stmt_semanal->close();
$stmt_mensal->close();
$conn->close();
