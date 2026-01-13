<?php

require_once dirname(__DIR__) . '/conexao.php';

// Consulta: traz onde NF de entrada está vazia ou NULL
$sql = "SELECT 
            rr.cnpj, 
            rr.razao_social, 
            rr.nota_fiscal, 
            DATE(rp.data_inicio_reparo) AS data_inicio_reparo,
            rr.quantidade_total, 
            rp.quantidade_parcial,
            rr.status,
            rr.numero_orcamento,
            rr.valor_orcamento,
            rr.setor
        FROM reparo_resumo rr
        LEFT JOIN reparo_parcial rp 
            ON rr.cnpj = rp.cnpj AND rr.nota_fiscal = rp.nota_fiscal
        WHERE rr.status = 'em_reparo'";

$result = $conn->query($sql);

$dados = [];

while ($row = $result->fetch_assoc()) {
    $dados[] = $row;
}

header('Content-Type: application/json');
echo json_encode($dados);
$conn->close();
?>
