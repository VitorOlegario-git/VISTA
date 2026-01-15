<?php
/**
 * TABELA DETALHADA - ÁREA DE REPARO
 * 10 colunas: data, NF, cliente, qtd total, qtd reparada, backlog, operador, status, valor, serviço
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../../BackEnd/conexao.php';
require_once '../../BackEnd/endpoint-helpers.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Parâmetros de filtro
    $inicio = isset($_GET['inicio']) ? $_GET['inicio'] : null;
    $fim = isset($_GET['fim']) ? $_GET['fim'] : null;
    $setor = isset($_GET['setor']) ? $_GET['setor'] : null;
    $operador = isset($_GET['operador']) ? $_GET['operador'] : null;
    
    if (!$inicio || !$fim) {
        sendError("Parâmetros 'inicio' e 'fim' são obrigatórios", 400);
    }
    
    // Converter datas DD/MM/YYYY para YYYY-MM-DD
    $dataInicio = DateTime::createFromFormat('d/m/Y', $inicio);
    $dataFim = DateTime::createFromFormat('d/m/Y', $fim);
    
    if (!$dataInicio || !$dataFim) {
        sendError("Formato de data inválido. Use DD/MM/YYYY", 400);
    }
    
    $inicioSQL = $dataInicio->format('Y-m-d');
    $fimSQL = $dataFim->format('Y-m-d');
    
    // Query principal
    $query = "SELECT 
                r.id,
                DATE_FORMAT(r.data_registro, '%d/%m/%Y') AS data_registro,
                r.nota_fiscal,
                r.cnpj,
                COALESCE(c.razao_social, 'Cliente Não Identificado') AS cliente,
                r.quantidade_total,
                COALESCE(r.quantidade_reparada, 0) AS quantidade_reparada,
                (r.quantidade_total - COALESCE(r.quantidade_reparada, 0)) AS backlog,
                COALESCE(r.operador, 'Não Atribuído') AS operador,
                r.setor,
                COALESCE(r.valor_orcamento, 0) AS valor_orcamento,
                COALESCE(r.servico, 'Não Especificado') AS servico,
                CASE 
                    WHEN r.quantidade_reparada >= r.quantidade_total THEN 'completo'
                    WHEN r.quantidade_reparada > 0 THEN 'parcial'
                    ELSE 'pendente'
                END AS status
              FROM reparo_resumo r
              LEFT JOIN clientes c ON r.cnpj = c.cnpj
              WHERE r.data_registro >= :inicio 
                AND r.data_registro <= :fim";
    
    $params = [
        ':inicio' => $inicioSQL,
        ':fim' => $fimSQL
    ];
    
    if ($setor) {
        $query .= " AND r.setor = :setor";
        $params[':setor'] = $setor;
    }
    
    if ($operador) {
        $query .= " AND r.operador = :operador";
        $params[':operador'] = $operador;
    }
    
    $query .= " ORDER BY r.data_registro DESC, r.id DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    $dados = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dados[] = [
            'id' => $row['id'],
            'data_registro' => $row['data_registro'],
            'nota_fiscal' => $row['nota_fiscal'],
            'cliente' => $row['cliente'],
            'quantidade_total' => (int)$row['quantidade_total'],
            'quantidade_reparada' => (int)$row['quantidade_reparada'],
            'backlog' => (int)$row['backlog'],
            'operador' => $row['operador'],
            'status' => $row['status'],
            'valor_orcamento' => number_format((float)$row['valor_orcamento'], 2, ',', '.'),
            'servico' => $row['servico']
        ];
    }
    
    sendSuccess($dados);
    
} catch (Exception $e) {
    error_log("Erro em tabela-detalhada.php: " . $e->getMessage());
    sendError("Erro ao buscar tabela detalhada: " . $e->getMessage(), 500);
}
