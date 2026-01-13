<?php

require_once dirname(__DIR__) . '/conexao.php';


// Consulta: traz onde NF de entrada está vazia ou NULL
$sql = "SELECT cnpj, razao_social, nota_fiscal, quantidade, quantidade_parcial, setor, operacao_destino
        FROM qualidade_registro
        WHERE operacao_destino = 'aguardando_NF_retorno'";

$result = $conn->query($sql);

$dados = [];

while ($row = $result->fetch_assoc()) {
    $dados[] = $row;
}

header('Content-Type: application/json');
echo json_encode($dados);
$conn->close();
?>
