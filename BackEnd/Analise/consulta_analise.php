<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";


// Consulta: traz onde NF de entrada está vazia ou NULL
$sql = "SELECT 
            cnpj, 
            razao_social, 
            nota_fiscal, 
            DATE(data_atualizacao) AS data_atualizacao, 
            quantidade_total, 
            status,
            setor
        FROM analise_resumo 
        WHERE status = 'em_analise'";

$result = $conn->query($sql);

$dados = [];

while ($row = $result->fetch_assoc()) {
    $dados[] = $row;
}

header('Content-Type: application/json');
echo json_encode($dados);
$conn->close();
?>
