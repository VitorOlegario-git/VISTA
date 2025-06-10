<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";


header('Content-Type: application/json');

$sql = "SELECT setor, status, COUNT(*) as total 
        FROM resumo_geral 
        GROUP BY setor, status 
        ORDER BY setor, status";

$result = $conn->query($sql);
$dados = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $setor = $row['setor'];
        $status = $row['status'];
        $total = $row['total'];

        if (!isset($dados[$setor])) {
            $dados[$setor] = [];
        }

        $dados[$setor][] = ['status' => $status, 'total' => $total];
    }

    echo json_encode(['success' => true, 'dados' => $dados]);
} else {
    echo json_encode(['success' => false, 'mensagem' => 'Nenhum dado encontrado.']);
}
?>
