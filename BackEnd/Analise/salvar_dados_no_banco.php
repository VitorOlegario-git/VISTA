<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false); // Compatibilidade adicional
header("Pragma: no-cache"); // Compatível com HTTP/1.0
header("Expires: 0"); // Expira imediatamente

error_reporting(E_ALL);
ini_set('display_errors', 1);

$tempo_limite = 1200; // 20 minutos

// Verifica inatividade
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $tempo_limite) {
    session_unset();
    session_destroy();
    header("Location: /localhost/FrontEnd/tela_login.php");
    exit();
}

// Verifica se a sessão está ativa
if (!isset($_SESSION['username'])) {
    header("Location: /localhost/FrontEnd/tela_login.php");
    exit();
}

$_SESSION['last_activity'] = time();


require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $jsonData = json_decode($_POST['jsonData'], true);
    $entrada_id = trim($_POST['entrada_id'] ?? '');

    if (empty($jsonData) || empty($entrada_id)) {
        echo json_encode(["error" => "Dados do Excel ou entrada_id ausentes"]);
        exit();
    }

    // Verifica se já existe dados com esse entrada_id
    $verifica = $conn->prepare("SELECT COUNT(*) FROM laudos_manutencao WHERE entrada_id = ?");
    $verifica->bind_param("s", $entrada_id);
    $verifica->execute();
    $verifica->bind_result($count);
    $verifica->fetch();
    $verifica->close();

    if ($count > 0) {
        echo json_encode(["error" => "Dados já foram cadastrados para esta entrada."]);
        exit();
    }

    $conn->begin_transaction();

    try {
        $sql = "INSERT INTO laudos_manutencao (entrada_id, imei, modelo, laudo, nf) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro ao preparar statement: " . $conn->error);
        }

        foreach ($jsonData as $index => $row) {
            $imei = $row['IMEI'] ?? '';
            $modelo = $row['MODELO'] ?? '';
            $laudo = $row['LAUDO'] ?? '';
            $nf = $row['NF'] ?? '';

            $stmt->bind_param("sssss", $entrada_id, $imei, $modelo, $laudo, $nf);
            if (!$stmt->execute()) {
                throw new Exception("Erro ao inserir linha $index: " . $stmt->error);
            }
        }

        $conn->commit();
        echo json_encode(["success" => true, "message" => "Dados inseridos com sucesso"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["error" => "Erro na gravação: " . $e->getMessage()]);
    } finally {
        $stmt->close();
        $conn->close();
    }

    exit();
}
?>
