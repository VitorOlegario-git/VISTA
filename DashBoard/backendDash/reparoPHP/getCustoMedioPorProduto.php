<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

header("Content-Type: application/json");

$data_inicio = !empty($_POST['data_inicial']) ? $_POST['data_inicial'] : "2000-01-01";
$data_fim = !empty($_POST['data_final']) ? $_POST['data_final'] : date("Y-m-d");

if (empty($data_inicio) || empty($data_fim)) {
    echo json_encode(["error" => "Datas não fornecidas"]);
    exit;
}

$sql = "
    SELECT 
        pc.produto, 
        AVG(pc.preco_venda) AS custo_medio
    FROM 
        apontamentos_gerados ag
    JOIN 
        produtos_catalogo pc ON ag.produto = pc.produto
    WHERE 
        ag.data_cadastro BETWEEN ? AND ?
    GROUP BY 
        pc.produto
    ORDER BY 
        custo_medio DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $data_inicio, $data_fim);
$stmt->execute();

$result = $stmt->get_result();
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = [
        "produto" => $row['produto'] ?? 'Desconhecido',
        "custo_medio" => number_format((float)$row['custo_medio'], 2, '.', '')
    ];
}

echo json_encode($data);
$conn->close();
?>
