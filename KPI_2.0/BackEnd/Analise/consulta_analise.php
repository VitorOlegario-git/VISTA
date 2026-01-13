<?php

session_start();

// Inclui o arquivo de conexão com o banco de dados
require_once dirname(__DIR__) . '/conexao.php';

// Define o cabeçalho para JSON
header('Content-Type: application/json');

$sql = "
    SELECT 
        r.cnpj, 
        r.razao_social, 
        r.nota_fiscal, 
        DATE(p.data_inicio_analise) AS data_inicio_analise,
        r.quantidade_total, 
        p.quantidade_parcial,
        r.status,
        r.setor
    FROM analise_resumo r
    LEFT JOIN analise_parcial p 
        ON r.cnpj = p.cnpj AND r.nota_fiscal = p.nota_fiscal
    WHERE r.status = 'em_analise'
";

$result = $conn->query($sql);

// Verifica erros na query
if (!$result) {
    echo json_encode(["error" => $conn->error]);
    exit;
}

$dados = [];

while ($row = $result->fetch_assoc()) {
    $dados[] = $row;
}

echo json_encode($dados);

$conn->close();

?>
