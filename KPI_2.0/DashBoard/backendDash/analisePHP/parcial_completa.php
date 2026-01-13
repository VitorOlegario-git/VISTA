<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';

$data_inicio = $_POST['data_inicial'] ?? null;
$data_fim    = $_POST['data_final']   ?? null;
$operador    = $_POST['operador']     ?? null;

try {
    $where = [];
    $params = [];
    $types = "";

    if ($data_inicio && $data_fim) {
        $where[] = "data_envio_orcamento BETWEEN ? AND ?";
        $params[] = $data_inicio;
        $params[] = $data_fim;
        $types .= "ss";
    }

    if (!empty($operador)) {
        $where[] = "operador = ?";
        $params[] = $operador;
        $types .= "s";
    }

    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    $sqlTotal = "SELECT COUNT(id) AS total FROM analise_parcial $whereClause";
    $sqlParciais = "SELECT COUNT(id) AS parciais FROM analise_parcial $whereClause" . 
                   (!empty($where) ? " AND" : " WHERE") . " quantidade_parcial < quantidade_total";

    // Total
    $stmtTotal = $conn->prepare($sqlTotal);
    if (!empty($params)) $stmtTotal->bind_param($types, ...$params);
    $stmtTotal->execute();
    $total = $stmtTotal->get_result()->fetch_assoc()['total'] ?? 0;

    // Parciais
    $stmtParciais = $conn->prepare($sqlParciais);
    if (!empty($params)) $stmtParciais->bind_param($types, ...$params);
    $stmtParciais->execute();
    $parciais = $stmtParciais->get_result()->fetch_assoc()['parciais'] ?? 0;

    $percentual = $total > 0 ? round(($parciais / $total) * 100, 2) : 0;

    echo json_encode([
        "total" => $total,
        "parciais" => $parciais,
        "percentual" => $percentual
    ]);
} catch (Exception $e) {
    echo json_encode(["erro" => $e->getMessage()]);
}
?>
