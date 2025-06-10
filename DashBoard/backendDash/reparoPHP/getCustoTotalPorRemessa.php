<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";
header("Content-Type: application/json");

$data_inicio = !empty($_POST['data_inicial']) ? $_POST['data_inicial'] : "2000-01-01";
$data_fim = !empty($_POST['data_final']) ? $_POST['data_final'] : date("Y-m-d");

if (empty($data_inicio) || empty($data_fim)) {
    echo json_encode(["error" => "Datas não fornecidas"]);
    exit;
}

// Primeiro, conta quantas vezes cada produto aparece
$sql = "
    SELECT produto, COUNT(*) AS quantidade
    FROM apontamentos_gerados
    WHERE data_cadastro BETWEEN ? AND ?
    GROUP BY produto
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $data_inicio, $data_fim);

if (!$stmt->execute()) {
    echo json_encode(["error" => "Erro na primeira consulta: " . $stmt->error]);
    exit;
}

$result = $stmt->get_result();
$data = [];

while ($row = $result->fetch_assoc()) {
    $produto = $row['produto'];
    $quantidade = (int)$row['quantidade'];

    // Agora busca o preço unitário na outra tabela
    $stmtPreco = $conn->prepare("SELECT preco_venda FROM produtos_catalogo WHERE produto = ? LIMIT 1");
    $stmtPreco->bind_param("s", $produto);
    $stmtPreco->execute();
    $resultPreco = $stmtPreco->get_result();
    $preco_venda = 0;

    if ($precoRow = $resultPreco->fetch_assoc()) {
        $preco_venda = (float)$precoRow['preco_venda'];
    }

    $data[] = [
        "produto" => $produto,
        "quantidade" => $quantidade,
        "preco_unitario" => number_format($preco_venda, 2, '.', ''),
        "valor_total" => number_format($quantidade * $preco_venda, 2, '.', '')
    ];

    $stmtPreco->close();
}

echo json_encode($data);
$conn->close();
?>
