<?php
/**
 * Gráfico: Parciais vs Completas
 * Quantidade parcial × total (mede retrabalho)
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
            DATE_FORMAT(data_inicio_analise, '%Y-%m-%d') AS data,
            SUM(CASE WHEN quantidade_analisada < quantidade_total THEN quantidade_analisada ELSE 0 END) AS parcial,
            SUM(CASE WHEN quantidade_analisada >= quantidade_total THEN quantidade_analisada ELSE 0 END) AS completa
        FROM analise_resumo
        WHERE data_inicio_analise >= ? AND data_inicio_analise <= ?
    ";

    $params = [$dataInicial, $dataFinal];

    if ($setor) {
        $sql .= " AND setor = ?";
        $params[] = $setor;
    }

    if ($operador) {
        $sql .= " AND operador_analise = ?";
        $params[] = $operador;
    }

    $sql .= " GROUP BY DATE_FORMAT(data_inicio_analise, '%Y-%m-%d') ORDER BY data ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $parciais = [];
    $completas = [];

    foreach ($results as $row) {
        $dataFormatada = date('d/m', strtotime($row['data']));
        $labels[] = $dataFormatada;
        $parciais[] = (int)$row['parcial'];
        $completas[] = (int)$row['completa'];
    }

    if (empty($labels)) {
        $labels = ['Sem dados'];
        $parciais = [0];
        $completas = [0];
    }

    sendSuccess([
        'labels' => $labels,
        'parciais' => $parciais,
        'completas' => $completas
    ]);

} catch (Exception $e) {
    error_log("Erro em grafico-parciais-completas.php: " . $e->getMessage());
    sendError('Erro ao gerar gráfico: ' . $e->getMessage(), 500);
}
?>
