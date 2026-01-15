<?php
/**
 * Gráfico: Distribuição por Setor
 * Retorna quantidade de equipamentos recebidos por setor
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
            COALESCE(setor, 'Não Especificado') AS setor,
            SUM(quantidade) AS total
        FROM recebimentos
        WHERE data_entrada >= ? AND data_entrada <= ?
    ";

    $params = [$dataInicial, $dataFinal];

    if ($setor) {
        $sql .= " AND setor = ?";
        $params[] = $setor;
    }

    if ($operador) {
        $sql .= " AND operador_recebimento = ?";
        $params[] = $operador;
    }

    $sql .= " GROUP BY setor ORDER BY total DESC LIMIT 10";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $valores = [];

    foreach ($results as $row) {
        $labels[] = $row['setor'];
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
    error_log("Erro em grafico-por-setor.php: " . $e->getMessage());
    sendError('Erro ao gerar gráfico por setor: ' . $e->getMessage(), 500);
}
?>
