<?php
/**
 * Tabela Detalhada - Recebimento
 * Lista todos os recebimentos com informações operacionais completas
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
            r.id,
            r.data_entrada AS data,
            r.nota_fiscal,
            r.cnpj,
            c.razao_social,
            r.quantidade,
            COALESCE(r.operacao_origem, 'entrada_recebimento') AS operacao_origem,
            COALESCE(r.operacao_destino, 'enviado_analise') AS operacao_destino,
            r.operador_recebimento AS operador,
            r.setor,
            CASE 
                WHEN ar.id IS NOT NULL THEN 'enviado_analise'
                ELSE 'aguardando_envio'
            END AS status
        FROM recebimentos r
        LEFT JOIN clientes c ON r.cnpj = c.cnpj
        LEFT JOIN analise_resumo ar ON r.nota_fiscal = ar.nota_fiscal
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

    $sql .= " ORDER BY r.data_entrada DESC, r.id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatar dados para a tabela
    $dadosFormatados = [];
    foreach ($results as $row) {
        $dadosFormatados[] = [
            'id' => (int)$row['id'],
            'data' => $row['data'],
            'nota_fiscal' => $row['nota_fiscal'],
            'cnpj' => $row['cnpj'],
            'razao_social' => $row['razao_social'] ?? 'Cliente não cadastrado',
            'quantidade' => (int)$row['quantidade'],
            'operacao_origem' => $row['operacao_origem'],
            'operacao_destino' => $row['operacao_destino'],
            'operador' => $row['operador'] ?? 'N/A',
            'setor' => $row['setor'] ?? 'N/A',
            'status' => $row['status']
        ];
    }

    sendSuccess($dadosFormatados);

} catch (Exception $e) {
    error_log("Erro em tabela-detalhada.php: " . $e->getMessage());
    sendError('Erro ao buscar dados da tabela: ' . $e->getMessage(), 500);
}
?>
