<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";


// Consulta: traz onde NF de entrada está vazia ou NULL
$sql = "SELECT setor, cnpj, razao_social, nota_fiscal, DATE_FORMAT(data_atualizacao, '%d/%m/%Y %H:%i:%s') as data_atualizacao
, quantidade_total, status
        FROM analise_resumo
        WHERE status = 'envio_analise'";

$result = $conn->query($sql);

$dados = [];

while ($row = $result->fetch_assoc()) {
    $dados[] = $row;
}

header('Content-Type: application/json');
echo json_encode($dados);
$conn->close();
?>
