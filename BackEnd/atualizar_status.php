<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/sistema/KPI_2.0/BackEnd/conexao.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $nova_operacao = $_POST['nova_operacao'];

    $sql = "UPDATE recebimento SET operacao = '$nova_operacao' WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Status atualizado com sucesso!'); window.location.href='painel.php';</script>";
    } else {
        echo "Erro ao atualizar: " . $conn->error;
    }

    $conn->close();
}
?>
