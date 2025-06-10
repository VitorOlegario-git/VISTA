<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

// Consulta: traz onde NF de entrada está vazia ou NULL
$sql = "SELECT 
            cnpj, 
            razao_social, 
            nota_fiscal, 
            DATE(data_registro) AS data_atualizacao, 
            quantidade_total, 
            quantidade_reparada,
            status,
            numero_orcamento,
            valor_orcamento,
            setor
        FROM reparo_resumo 
        WHERE status = 'em_reparo'";

$result = $conn->query($sql);

$dados = [];

while ($row = $result->fetch_assoc()) {
    $dados[] = $row;
}

header('Content-Type: application/json');
echo json_encode($dados);
$conn->close();
?>
