<?php
/**
 * Tabela Detalhada - Análise
 * Lista todas as análises com informações operacionais completas
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
            ar.id,
            ar.data_inicio_analise AS data_inicio,
            ar.nota_fiscal,
            ar.cnpj,
            c.razao_social,
            ar.quantidade_total,
            COALESCE(ar.quantidade_analisada, 0) AS quantidade_analisada,
            (ar.quantidade_total - COALESCE(ar.quantidade_analisada, 0)) AS backlog,
            ar.operador_analise AS operador,
            ar.setor,
            COALESCE(ar.valor_orcamento, 0) AS valor_orcamento,
            CASE 
                WHEN ar.quantidade_analisada >= ar.quantidade_total THEN 'completo'
                WHEN ar.quantidade_analisada > 0 THEN 'parcial'
                ELSE 'pendente'
            END AS status
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

    $sql .= " ORDER BY ar.data_inicio_analise DESC, ar.id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatar dados
    $dadosFormatados = [];
    foreach ($results as $row) {
        $dadosFormatados[] = [
            'id' => (int)$row['id'],
            'data_inicio' => $row['data_inicio'],
            'nota_fiscal' => $row['nota_fiscal'],
            'cnpj' => $row['cnpj'],
            'razao_social' => $row['razao_social'] ?? 'Cliente não cadastrado',
            'quantidade_total' => (int)$row['quantidade_total'],
            'quantidade_analisada' => (int)$row['quantidade_analisada'],
            'backlog' => (int)$row['backlog'],
            'operador' => $row['operador'] ?? 'N/A',
            'setor' => $row['setor'] ?? 'N/A',
            'valor_orcamento' => (float)$row['valor_orcamento'],
            'status' => $row['status']
        ];
    }

    sendSuccess($dadosFormatados);

} catch (Exception $e) {
    error_log("Erro em tabela-detalhada.php: " . $e->getMessage());
    sendError('Erro ao buscar dados da tabela: ' . $e->getMessage(), 500);
}
?>
