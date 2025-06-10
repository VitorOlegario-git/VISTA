<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

header('Content-Type: application/json');

$data_inicio = !empty($_POST['data_inicial']) ? $_POST['data_inicial'] : null;
$data_fim    = !empty($_POST['data_final'])   ? $_POST['data_final']   : null;

try {
    $where = [];
    $params = [];
    $types = "";

    if ($data_inicio && $data_fim) {
        $where[] = "data_inicio_analise BETWEEN ? AND ?";
        $params[] = $data_inicio;
        $params[] = $data_fim;
        $types .= "ss";
    }

    // WHERE comum para ambas as queries
    $whereClause = "";
    if (!empty($where)) {
        $whereClause = "WHERE " . implode(" AND ", $where);
    }

    // Query total de análises
    $sqlTotal = "SELECT COUNT(id) AS total FROM analise_parcial $whereClause";

    // Query de parciais: quantidade_parcial < quantidade_total
    $sqlParciais = "SELECT COUNT(id) AS parciais FROM analise_parcial $whereClause" . 
                   (!empty($where) ? " AND" : " WHERE") . " quantidade_parcial < quantidade_total";

    // Prepara total
    $stmtTotal = $conn->prepare($sqlTotal);
    if (!empty($params)) {
        $stmtTotal->bind_param($types, ...$params);
    }
    $stmtTotal->execute();
    $total = $stmtTotal->get_result()->fetch_assoc()['total'] ?? 0;

    // Prepara parciais
    $stmtParciais = $conn->prepare($sqlParciais);
    if (!empty($params)) {
        $stmtParciais->bind_param($types, ...$params);
    }
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
