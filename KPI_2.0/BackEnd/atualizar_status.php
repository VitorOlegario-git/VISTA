<?php
require_once dirname(__DIR__) . '/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $nova_operacao = $_POST['nova_operacao'];

    // Usar prepared statement para evitar SQL Injection
    $stmt = $conn->prepare("UPDATE recebimento SET operacao = ? WHERE id = ?");
    $stmt->bind_param('si', $nova_operacao, $id);
    if ($stmt->execute()) {
        echo "<script>alert('Status atualizado com sucesso!'); window.location.href='painel.php';</script>";
    } else {
        echo "Erro ao atualizar: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
}
?>
