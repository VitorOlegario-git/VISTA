<?php
/**
 * Gráfico: Operações (Origem → Destino)
 * Mostra o fluxo de operações do recebimento
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

    // Buscar operações de recebimento e seus destinos
    $sql = "
        SELECT 
            CONCAT(
                COALESCE(r.operacao_origem, 'Entrada'), 
                ' → ', 
                COALESCE(r.operacao_destino, 'Análise')
            ) AS fluxo,
            SUM(r.quantidade) AS total
        FROM recebimentos r
        WHERE r.data_entrada >= ? AND r.data_entrada <= ?
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

    $sql .= " GROUP BY fluxo ORDER BY total DESC LIMIT 8";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $valores = [];

    foreach ($results as $row) {
        $labels[] = $row['fluxo'];
        $valores[] = (int)$row['total'];
    }

    if (empty($labels)) {
        $labels = ['Entrada → Análise'];
        $valores = [0];
    }

    sendSuccess([
        'labels' => $labels,
        'valores' => $valores
    ]);

} catch (Exception $e) {
    error_log("Erro em grafico-operacoes.php: " . $e->getMessage());
    sendError('Erro ao gerar gráfico de operações: ' . $e->getMessage(), 500);
}
?>
