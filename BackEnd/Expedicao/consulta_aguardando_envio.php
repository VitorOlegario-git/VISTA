<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";



// Consulta: traz onde NF de entrada está vazia ou NULL
$sql = "SELECT cnpj, razao_social, nota_fiscal, data_envio_expedicao, quantidade, setor, operacao_destino, nota_fiscal_retorno
        FROM expedicao_registro
        WHERE operacao_destino = 'envio_expedicao'";

$result = $conn->query($sql);

$dados = [];

while ($row = $result->fetch_assoc()) {
    $dados[] = $row;
}

header('Content-Type: application/json');
echo json_encode($dados);
$conn->close();
?>
