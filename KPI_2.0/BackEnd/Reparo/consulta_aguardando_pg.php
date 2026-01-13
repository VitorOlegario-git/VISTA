<?php

require_once dirname(__DIR__) . '/conexao.php';


// Consulta: traz onde NF de entrada está vazia ou NULL
$sql = "SELECT cnpj, razao_social, nota_fiscal, DATE(data_registro) AS data_atualizacao, quantidade_total, status, setor, numero_orcamento, valor_orcamento
        FROM reparo_resumo
        WHERE status = 'aguardando_pg'";

$result = $conn->query($sql);

$dados = [];

while ($row = $result->fetch_assoc()) {
    $dados[] = $row;
}

header('Content-Type: application/json');
echo json_encode($dados);
$conn->close();
?>
