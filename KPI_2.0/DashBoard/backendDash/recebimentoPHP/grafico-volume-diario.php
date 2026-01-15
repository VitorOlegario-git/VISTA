<?php
/**
 * Gráfico: Volume Diário de Recebimentos
 * Retorna série temporal com remessas e equipamentos recebidos por dia
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
            DATE_FORMAT(data_entrada, '%Y-%m-%d') AS data,
            COUNT(DISTINCT id) AS total_remessas,
            SUM(quantidade) AS total_equipamentos
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

    $sql .= " GROUP BY DATE_FORMAT(data_entrada, '%Y-%m-%d') ORDER BY data ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Criar arrays de labels e dados
    $labels = [];
    $remessas = [];
    $equipamentos = [];

    foreach ($results as $row) {
        $dataFormatada = date('d/m', strtotime($row['data']));
        $labels[] = $dataFormatada;
        $remessas[] = (int)$row['total_remessas'];
        $equipamentos[] = (int)$row['total_equipamentos'];
    }

    // Se não houver dados, retornar arrays vazios
    if (empty($labels)) {
        $labels = ['Sem dados'];
        $remessas = [0];
        $equipamentos = [0];
    }

    sendSuccess([
        'labels' => $labels,
        'remessas' => $remessas,
        'equipamentos' => $equipamentos,
        'periodo' => [
            'inicio' => $dataInicial,
            'fim' => $dataFinal
        ]
    ]);

} catch (Exception $e) {
    error_log("Erro em grafico-volume-diario.php: " . $e->getMessage());
    sendError('Erro ao gerar gráfico de volume diário: ' . $e->getMessage(), 500);
}
?>
