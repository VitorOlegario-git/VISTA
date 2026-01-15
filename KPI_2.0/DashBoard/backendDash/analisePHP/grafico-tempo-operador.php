<?php
/**
 * Gráfico: Tempo Médio por Operador
 * Identifica gargalos humanos
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
            COALESCE(operador_analise, 'Não Identificado') AS operador,
            AVG(DATEDIFF(data_envio_orcamento, data_inicio_analise)) AS tempo_medio
        FROM analise_resumo
        WHERE data_inicio_analise >= ? AND data_inicio_analise <= ?
        AND data_envio_orcamento IS NOT NULL
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

    $sql .= " GROUP BY operador_analise ORDER BY tempo_medio ASC LIMIT 10";

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
    error_log("Erro em grafico-tempo-operador.php: " . $e->getMessage());
    sendError('Erro ao gerar gráfico: ' . $e->getMessage(), 500);
}
?>
