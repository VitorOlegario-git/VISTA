<?php
/**
 * Gráfico: Análises por Cliente
 * Distribuição do volume analisado por cliente
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../endpoint-helpers.php';

try {
    $dataInicial = $_GET['data_inicial'] ?? null;
    $dataFinal = $_GET['data_final'] ?? null;
    $setor = $_GET['setor'] ?? null;
    $operador = $_GET['operador'] ?? null;

    if (!$dataInicial || !$dataFinal) {
        sendError('Parâmetros data_inicial e data_final são obrigatórios', 400);
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();

    $sql = "
        SELECT 
            c.razao_social,
            SUM(COALESCE(ar.quantidade_analisada, 0)) AS total
        FROM analise_resumo ar
        LEFT JOIN clientes c ON ar.cnpj = c.cnpj
        WHERE ar.data_inicio_analise >= ? AND ar.data_inicio_analise <= ?
    ";

    $params = [$dataInicial, $dataFinal];

    if ($setor) {
        $sql .= " AND ar.setor = ?";
        $params[] = $setor;
    }

    if ($operador) {
        $sql .= " AND ar.operador_analise = ?";
        $params[] = $operador;
    }

    $sql .= " GROUP BY ar.cnpj, c.razao_social ORDER BY total DESC LIMIT 10";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $valores = [];

    foreach ($results as $row) {
        $labels[] = $row['razao_social'] ?? 'Cliente não identificado';
        $valores[] = (int)$row['total'];
    }

    if (empty($labels)) {
        $labels = ['Sem dados'];
        $valores = [0];
    }

    sendSuccess([
        'labels' => $labels,
        'valores' => $valores
    ]);

} catch (Exception $e) {
    error_log("Erro em grafico-por-cliente.php: " . $e->getMessage());
    sendError('Erro ao gerar gráfico: ' . $e->getMessage(), 500);
}
?>
