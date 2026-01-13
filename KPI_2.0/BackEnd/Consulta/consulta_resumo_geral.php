<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/conexao.php';
header("Content-Type: application/json");

function limpar($valor) {
    return htmlspecialchars(strip_tags(trim($valor)));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $imei = limpar($_POST['imei'] ?? '');
    $cnpj = limpar($_POST['cnpj'] ?? '');
    $nota_fiscal_input = limpar($_POST['nota_fiscal'] ?? ''); // NF vinda do formulário
    $cod_rastreio = limpar($_POST['cod_rast'] ?? '');
    $status = limpar($_POST['status'] ?? '');
    $setor = limpar($_POST['setor'] ?? '');

    $params = [];
    $types = "";
    $log_debug = [];

    if (!empty($imei)) {
        // Busca NF a partir do IMEI
        $stmtImei = $conn->prepare("SELECT nf FROM laudos_manutencao WHERE imei = ? LIMIT 1");
        $stmtImei->bind_param("s", $imei);
        $stmtImei->execute();
        $resultImei = $stmtImei->get_result();

        if ($resultImei && $row = $resultImei->fetch_assoc()) {
            $nf_from_imei = $row['nf'];
            $log_debug['nf_encontrada'] = $nf_from_imei;

            // Consulta com NF do IMEI
            $sql = "SELECT * FROM resumo_geral WHERE nota_fiscal = ?";
            $params[] = $nf_from_imei;
            $types .= "s";

        } else {
            echo json_encode([
                "success" => false,
                "mensagem" => "IMEI não encontrado na base de laudos.",
                "imei_recebido" => $imei
            ]);
            exit();
        }

        $stmtImei->close();
    } else {
        // Consulta padrão por CNPJ, NF digitada, etc.
        $sql = "SELECT * FROM resumo_geral WHERE 1=1";

        if (!empty($cnpj)) {
            $sql .= " AND cnpj = ?";
            $params[] = $cnpj;
            $types .= "s";
        }

        if (!empty($nota_fiscal_input)) {
            $sql .= " AND nota_fiscal = ?";
            $params[] = $nota_fiscal_input;
            $types .= "s";
        }

        if (!empty($cod_rastreio)) {
            $sql .= " AND codigo_rastreio_entrada LIKE ?";
            $params[] = "%$cod_rastreio%";
            $types .= "s";
        }

        if (!empty($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
            $types .= "s";
        }

        if (!empty($setor)) {
            $sql .= " AND setor = ?";
            $params[] = $setor;
            $types .= "s";
        }
    }

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $dados = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            "success" => true,
            "dados" => $dados,
            "debug" => $log_debug,
            "query" => $sql,
            "parametros" => $params
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "mensagem" => "Erro na consulta: " . $stmt->error
        ]);
    }

    $stmt->close();
    $conn->close();
}
?>
