<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . "/sistema/KPI_2.0/BackEnd/conexao.php";
header("Content-Type: application/json");

$data_inicio = !empty($_POST['data_inicial']) ? $_POST['data_inicial'] : "2000-01-01";
$data_fim = !empty($_POST['data_final']) ? $_POST['data_final'] : date("Y-m-d");
$operador = isset($_POST['operador']) ? trim($_POST['operador']) : "";

// Passo 1: Buscar orçamentos válidos da tabela reparo_parcial dentro do período e com operador
$sqlOrcamentos = "SELECT DISTINCT numero_orcamento FROM reparo_parcial WHERE data_solicitacao_nf BETWEEN ? AND ?";

$params = [$data_inicio, $data_fim];
$types = "ss";

if (!empty($operador)) {
    $sqlOrcamentos .= " AND operador = ?";
    $params[] = $operador;
    $types .= "s";
}

$stmt = $conn->prepare($sqlOrcamentos);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$orcamentosValidos = [];
while ($row = $result->fetch_assoc()) {
    if (!empty($row['numero_orcamento'])) {
        $orcamentosValidos[] = $row['numero_orcamento'];
    }
}
$stmt->close();

if (empty($orcamentosValidos)) {
    echo json_encode([]); // nenhum dado
    exit;
}

// Passo 2: Buscar apontamentos com esses orçamentos
$placeholders = implode(',', array_fill(0, count($orcamentosValidos), '?'));
$sql = "
    SELECT produto, COUNT(*) AS quantidade
    FROM apontamentos_gerados
    WHERE orcamento IN ($placeholders)
    GROUP BY produto 
    LIMIT 10
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat("s", count($orcamentosValidos)), ...$orcamentosValidos);
$stmt->execute();
$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $produto = $row['produto'];
    $quantidade = (int)$row['quantidade'];

    // Busca preço no catálogo
    $stmtPreco = $conn->prepare("SELECT preco_venda FROM produtos_catalogo WHERE produto = ? LIMIT 1");
    $stmtPreco->bind_param("s", $produto);
    $stmtPreco->execute();
    $resultPreco = $stmtPreco->get_result();

    $preco_venda = 0;
    if ($precoRow = $resultPreco->fetch_assoc()) {
        $preco_venda = (float)$precoRow['preco_venda'];
    }
    $stmtPreco->close();

    $data[] = [
        "produto" => $produto,
        "quantidade" => $quantidade,
        "preco_unitario" => number_format($preco_venda, 2, '.', ''),
        "valor_total" => number_format($quantidade * $preco_venda, 2, '.', '')
    ];
}

echo json_encode($data);
$conn->close();
?>
