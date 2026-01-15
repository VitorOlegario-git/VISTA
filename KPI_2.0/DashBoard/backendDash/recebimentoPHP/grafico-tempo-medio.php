<?php
/**
 * Gráfico: Tempo Médio por Operador
 * Mostra o tempo médio de processamento por operador
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
            COALESCE(r.operador_recebimento, 'Não Identificado') AS operador,
            AVG(DATEDIFF(ar.data_analise, r.data_entrada)) AS tempo_medio
        FROM recebimentos r
        LEFT JOIN analise_resumo ar ON r.nota_fiscal = ar.nota_fiscal
        WHERE r.data_entrada >= ? AND r.data_entrada <= ?
        AND ar.data_analise IS NOT NULL
    ";

    $params = [$dataInicial, $dataFinal];

    if ($setor) {
        $sql .= " AND r.setor = ?";
        $params[] = $setor;
    }

    if ($operador) {
        $sql .= " AND r.operador_recebimento = ?";
        $params[] = $operador;
    }

    $sql .= " GROUP BY r.operador_recebimento ORDER BY tempo_medio ASC LIMIT 10";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $valores = [];

    foreach ($results as $row) {
        $labels[] = $row['operador'];
        $valores[] = round((float)$row['tempo_medio'], 1);
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
    error_log("Erro em grafico-tempo-medio.php: " . $e->getMessage());
    sendError('Erro ao gerar gráfico de tempo médio: ' . $e->getMessage(), 500);
}
?>
