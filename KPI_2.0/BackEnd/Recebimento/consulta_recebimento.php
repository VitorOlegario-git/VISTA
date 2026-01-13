<?php

require_once dirname(__DIR__) . '/conexao.php';


// Consulta: traz onde NF de entrada está vazia ou NULL
$sql = "SELECT cod_rastreio, setor, cnpj, razao_social, data_recebimento,quantidade, operacao_destino, observacoes 
        FROM recebimentos 
        WHERE operacao_destino = 'aguardando_nf'";

$result = $conn->query($sql);

$dados = [];

while ($row = $result->fetch_assoc()) {
    $dados[] = $row;
}

header('Content-Type: application/json');
echo json_encode($dados);
$conn->close();
?>
