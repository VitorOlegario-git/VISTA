<?php
/**
 * TABELA DETALHADA - QUALIDADE
 * Visão operacional granular dos registros de qualidade
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../../BackEnd/conexao.php';
require_once '../../BackEnd/endpoint-helpers.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $inicio = isset($_GET['inicio']) ? $_GET['inicio'] : null;
    $fim = isset($_GET['fim']) ? $_GET['fim'] : null;
    $setor = isset($_GET['setor']) ? $_GET['setor'] : null;
    $operador = isset($_GET['operador']) ? $_GET['operador'] : null;
    $busca = isset($_GET['busca']) ? $_GET['busca'] : null;
    $ordenar = isset($_GET['ordenar']) ? $_GET['ordenar'] : 'data_inicio_qualidade';
    $direcao = isset($_GET['direcao']) ? strtoupper($_GET['direcao']) : 'DESC';
    
    if (!$inicio || !$fim) {
        sendError("Parâmetros 'inicio' e 'fim' são obrigatórios", 400);
    }
    
    $dataInicio = DateTime::createFromFormat('d/m/Y', $inicio);
    $dataFim = DateTime::createFromFormat('d/m/Y', $fim);
    
    if (!$dataInicio || !$dataFim) {
        sendError("Formato de data inválido. Use DD/MM/YYYY", 400);
    }
    
    $inicioSQL = $dataInicio->format('Y-m-d');
    $fimSQL = $dataFim->format('Y-m-d');
    
    // Validação de ordenação
    $colunasPermitidas = [
        'data_inicio_qualidade',
        'nota_fiscal',
        'razao_social',
        'quantidade',
        'quantidade_parcial',
        'operador',
        'status',
        'data_envio_expedicao'
    ];
    
    if (!in_array($ordenar, $colunasPermitidas)) {
        $ordenar = 'data_inicio_qualidade';
    }
    
    if ($direcao !== 'ASC' && $direcao !== 'DESC') {
        $direcao = 'DESC';
    }
    
    // Query base
    $query = "SELECT 
                qr.id,
                DATE_FORMAT(qr.data_inicio_qualidade, '%d/%m/%Y') AS data_inicio,
                qr.nota_fiscal,
                COALESCE(c.razao_social, 'Não informado') AS razao_social,
                qr.quantidade AS quantidade_total,
                COALESCE(qr.quantidade_parcial, 0) AS quantidade_aprovada,
                (qr.quantidade - COALESCE(qr.quantidade_parcial, 0)) AS reprovadas,
                qr.operador,
                CASE 
                    WHEN qr.data_envio_expedicao IS NOT NULL THEN 'Enviado'
                    WHEN COALESCE(qr.quantidade_parcial, 0) > 0 THEN 'Em Análise'
                    ELSE 'Aguardando'
                END AS status,
                COALESCE(qr.motivo_reprovacao, '-') AS motivo,
                DATE_FORMAT(qr.data_envio_expedicao, '%d/%m/%Y') AS data_envio
              FROM qualidade_registro qr
              LEFT JOIN clientes c ON qr.cnpj = c.cnpj
              WHERE qr.data_inicio_qualidade >= :inicio 
                AND qr.data_inicio_qualidade <= :fim";
    
    $params = [':inicio' => $inicioSQL, ':fim' => $fimSQL];
    
    if ($setor) {
        $query .= " AND qr.setor = :setor";
        $params[':setor'] = $setor;
    }
    
    if ($operador) {
        $query .= " AND qr.operador = :operador";
        $params[':operador'] = $operador;
    }
    
    if ($busca) {
        $query .= " AND (qr.nota_fiscal LIKE :busca 
                    OR c.razao_social LIKE :busca 
                    OR qr.operador LIKE :busca 
                    OR qr.motivo_reprovacao LIKE :busca)";
        $params[':busca'] = '%' . $busca . '%';
    }
    
    $query .= " ORDER BY " . $ordenar . " " . $direcao;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    $registros = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $taxaReprovacao = $row['quantidade_total'] > 0 
            ? ($row['reprovadas'] / $row['quantidade_total']) * 100 
            : 0;
        
        $registros[] = [
            'id' => (int)$row['id'],
            'data_inicio' => $row['data_inicio'],
            'nota_fiscal' => $row['nota_fiscal'],
            'cliente' => $row['razao_social'],
            'quantidade_total' => (int)$row['quantidade_total'],
            'quantidade_aprovada' => (int)$row['quantidade_aprovada'],
            'reprovadas' => (int)$row['reprovadas'],
            'operador' => $row['operador'],
            'status' => $row['status'],
            'motivo' => $row['motivo'],
            'data_envio' => $row['data_envio'] ?? '-',
            'taxa_reprovacao' => round($taxaReprovacao, 1),
            'destaque' => $taxaReprovacao > 15 ? 'critical' : ($taxaReprovacao > 5 ? 'warning' : 'normal')
        ];
    }
    
    sendSuccess([
        'registros' => $registros,
        'total' => count($registros)
    ]);
    
} catch (Exception $e) {
    error_log("Erro em tabela-detalhada.php: " . $e->getMessage());
    sendError("Erro ao buscar tabela detalhada: " . $e->getMessage(), 500);
}
