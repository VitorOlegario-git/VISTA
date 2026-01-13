<?php
// Habilitar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';

// Nome do operador fixo
$operador = "Ederson Santos";

// Filtro de datas
$data_inicio = $_POST['data_inicio'] ?? '';
$data_fim = $_POST['data_fim'] ?? '';

$whereData = '';
$params = [];
$types = "";

// Vamos montar dinamicamente os blocos SQL e parâmetros
function gerarBloco($tabela, $setor_nome, $campo_data) {
    global $operador, $data_inicio, $data_fim, $whereData, $params, $types;

    $where = "operador = ?";
    $params[] = $operador;
    $types .= "s";

    if (!empty($data_inicio) && !empty($data_fim)) {
        $where .= " AND $campo_data BETWEEN ? AND ?";
        $params[] = $data_inicio . " 00:00:00";
        $params[] = $data_fim . " 23:59:59";
        $types .= "ss";
    }

    if ($tabela === "qualidade_registro") {
        return "
            SELECT 
                nota_fiscal,
                '$setor_nome' AS setor,
                razao_social,
                operacao_destino AS status,
                quantidade AS quantidade_total,
                $campo_data AS data_registro
            FROM $tabela
            WHERE $where
        ";
    } else {
        return "
            SELECT 
                nota_fiscal,
                '$setor_nome' AS setor,
                razao_social,
                operacao_destino AS status,
                quantidade_total,
                $campo_data AS data_registro
            FROM $tabela
            WHERE $where
        ";
    }
}

// Montar os blocos das 3 tabelas
$blocos = [
    gerarBloco("analise_parcial", "Análise", "data_registro"),
    gerarBloco("reparo_parcial", "Reparo", "data_registro"),
    gerarBloco("qualidade_registro", "Qualidade", "data_cadastro")
];

// Montar SQL final
$sql = "
    SELECT 
        nota_fiscal, setor, razao_social, quantidade_total, status, data_registro
    FROM (
        " . implode(" UNION ALL ", $blocos) . "
    ) AS atividades
    ORDER BY data_registro DESC
";

// Preparar e executar
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
if (!$result) {
    die("Erro na consulta: " . $conn->error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Relatório - <?= htmlspecialchars($operador) ?></title>
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-E...==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #007bff; color: white; }
        form { margin-bottom: 20px; }
        input[type="date"] { padding: 5px; margin-right: 10px; }
        button {
            padding: 10px 15px;
            margin-right: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        #tabela-relatorio {
            margin-top: 20px;
            width: 100%;
            border-collapse: collapse;
        }
        #tabela-relatorio th, #tabela-relatorio td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }
        #tabela-relatorio th {
            background: #007bff;
            color: white;
        }
        #tabela-relatorio tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        #tabela-relatorio tr:hover {
            background-color: #ddd;
        }
        .fas {
            margin-right: 5px;
        }
        button {
            display: inline-flex;
            align-items: center;
        }
        button i {
            margin-right: 5px;
        }
        button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        button:disabled i {
            color: #666;
        }
        button:disabled:hover {
            background-color: #ccc;
        }
        button:disabled:hover i {
            color: #666;
        }
        button:enabled i {
            color: white;
        }
        button:enabled:hover i {
            color: white;
        }
        button:enabled:hover {
            background-color: #0056b3;
        }
        button:enabled:active {
            background-color: #004494;
        }
        button:enabled:active i {
            color: white;
        }
        button:enabled:focus {
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
        button:enabled:focus i {
            color: white;
        }
        button:enabled:focus:hover {
            background-color: #004494;
        }
        button:enabled:focus:hover i {
            color: white;
        }
        button:enabled:focus:active {
            background-color: #003366;
        }
        button:enabled:focus:active i {
            color: white;
        }
        

    </style>
</head>
<body>

    <h2>Relatório de atividades - <?= htmlspecialchars($operador) ?></h2>

    <!-- Filtro por data -->
    <form method="POST">
        <label>Data início: <input type="date" name="data_inicio" value="<?= htmlspecialchars($data_inicio) ?>"></label>
        <label>Data fim: <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>"></label>
        <button type="submit">Filtrar</button>
    </form>

    <!-- Botões de exportação -->
    <button onclick="exportToExcel()">
       <i class="fas fa-file-excel"></i> Exportar para Excel
    </button>
    <button onclick="exportToPDF()">
       <i class="fas fa-file-pdf"></i> Exportar para PDF
    </button>

    <table id="tabela-relatorio">
        <tr>
            <th>NF</th>
            <th>Setor</th>
            <th>Cliente</th>
            <th>Status</th>
            <th>Qtd</th>
            <th>Data Registro</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['nota_fiscal']) ?></td>
            <td><?= $row['setor'] ?></td>
            <td><?= htmlspecialchars($row['razao_social']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
            <td><?= $row['quantidade_total'] ?></td>
            <td><?= date('d/m/Y H:i:s', strtotime($row['data_registro'])) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <!-- Scripts para exportação -->
     
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script>
        function exportToExcel() {
            const table = document.getElementById("tabela-relatorio");
            const wb = XLSX.utils.table_to_book(table, {sheet: "Relatório"});
            XLSX.writeFile(wb, "relatorio_<?= $operador ?>.xlsx");
        }

        async function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            doc.text("Relatório de atividades - <?= $operador ?>", 14, 15);
            doc.autoTable({ html: '#tabela-relatorio', startY: 20 });
            doc.save("relatorio_<?= $operador ?>.pdf");
        }
    </script>
</body>
</html>
