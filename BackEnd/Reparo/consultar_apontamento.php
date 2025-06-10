<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

$cnpj = $_GET['cnpj'] ?? '';
$nf = $_GET['nota_fiscal'] ?? '';

$sql = "SELECT id, entrada_id, imei, modelo, garantia, imei_devol, reclamacao, produto, servico, ocorrencia, cond_garantia_violada, orcamento, data_cadastro
        FROM apontamentos_gerados
        WHERE cnpj = ? AND nota_fiscal = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $cnpj, $nf);
$stmt->execute();
$result = $stmt->get_result();

$dados = [];

while ($row = $result->fetch_assoc()) {
    $dados[] = $row;
}

if (count($dados) > 0) {
    echo json_encode(["itens" => $dados]);
} else {
    echo json_encode(["error" => "Nenhum apontamento encontrado para esta remessa."]);
}
?>