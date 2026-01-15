<?php

session_start();

// Inclui o arquivo de conexão com o banco de dados
require_once dirname(__DIR__) . '/conexao.php';
// Verifica se a requisição é do tipo GET
 

$sql = "SELECT setor, cnpj, razao_social, nota_fiscal, DATE_FORMAT(data_atualizacao, '%d/%m/%Y %H:%i:%s') as data_atualizacao
, quantidade_total, quantidade_analisada, status
        FROM analise_resumo
        WHERE status = 'envio_analise'";


$result = $conn->query($sql);

$dados = [];

// Se a consulta retornou resultados, armazena os dados em um array
while ($row = $result->fetch_assoc()) {
    $dados[] = $row;
}


echo json_encode($dados);

$conn->close();
?>
