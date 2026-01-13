<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';
header('Content-Type: text/html; charset=utf-8');

/* ======================= Entrada ======================= */
$operadorParam = $_GET['operador'] ?? $_POST['operador'] ?? '';
$operadorParam = trim(str_replace('_', ' ', $operadorParam));
if ($operadorParam === '') $operadorParam = "Vitor Olegario";

/* normalização opcional */
$map = [
  'ronyrodrigues' => 'Rony Rodrigues',
  'RonyRodrigues' => 'Rony Rodrigues',
];
$operador = $map[$operadorParam] ?? $operadorParam;

$data_inicio = $_POST['data_inicio'] ?? $_GET['data_inicio'] ?? '';
$data_fim    = $_POST['data_fim']    ?? $_GET['data_fim']    ?? '';

/* =================== Builder de eventos =================== */
$parts  = [];
$params = [];
$types  = "";

/* helper para empurrar bloco + binds */
function addEvento(&$parts, &$params, &$types, $sql, $binds) {
  $parts[] = $sql;
  foreach ($binds as $b) { $params[] = $b; $types .= "s"; }
}

/* =============== ANÁLISE =============== */
/* Início Análise (usa data_inicio_analise; se vier 00:00:00, usa TIME(data_registro)) */
$binds = [$operador];
$whereData = "";
if ($data_inicio && $data_fim) {
  $whereData = " AND (
      CASE
        WHEN ap.data_inicio_analise IS NULL THEN NULL
        WHEN TIME(ap.data_inicio_analise) <> '00:00:00' THEN ap.data_inicio_analise
        WHEN ap.data_registro IS NOT NULL AND TIME(ap.data_registro) <> '00:00:00'
          THEN TIMESTAMP(DATE(ap.data_inicio_analise), TIME(ap.data_registro))
        ELSE ap.data_inicio_analise
      END
    ) BETWEEN ? AND ? ";
  $binds[] = $data_inicio . " 00:00:00";
  $binds[] = $data_fim    . " 23:59:59";
}
addEvento(
  $parts, $params, $types,
  "SELECT ap.nota_fiscal, 'Análise' AS setor, ap.razao_social,
          'Início Análise' AS status, ap.quantidade_parcial AS quantidade,
          CASE
            WHEN ap.data_inicio_analise IS NULL THEN NULL
            WHEN TIME(ap.data_inicio_analise) <> '00:00:00' THEN ap.data_inicio_analise
            WHEN ap.data_registro IS NOT NULL AND TIME(ap.data_registro) <> '00:00:00'
              THEN TIMESTAMP(DATE(ap.data_inicio_analise), TIME(ap.data_registro))
            ELSE ap.data_inicio_analise
          END AS data_evento
   FROM analise_parcial ap
   WHERE ap.operador = ?
     AND ap.data_inicio_analise IS NOT NULL
     {$whereData}",
  $binds
);


/* Envio Orçamento (usa data_envio_orcamento; se vier 00:00:00, pega o TIME de data_registro no mesmo dia) */
$binds = [$operador];
$whereData = "";
if ($data_inicio && $data_fim) {
  $whereData = " AND (
      CASE
        WHEN ap.data_envio_orcamento IS NULL THEN NULL
        WHEN TIME(ap.data_envio_orcamento) <> '00:00:00' THEN ap.data_envio_orcamento
        WHEN ap.data_registro IS NOT NULL
             AND DATE(ap.data_envio_orcamento) = DATE(ap.data_registro)
          THEN TIMESTAMP(DATE(ap.data_envio_orcamento), TIME(ap.data_registro))
        ELSE ap.data_envio_orcamento
      END
    ) BETWEEN ? AND ? ";
  $binds[] = $data_inicio . " 00:00:00";
  $binds[] = $data_fim    . " 23:59:59";
}
addEvento(
  $parts, $params, $types,
  "SELECT ap.nota_fiscal, 'Análise' AS setor, ap.razao_social,
          'Envio Orçamento' AS status, ap.quantidade_parcial AS quantidade,
          CASE
            WHEN ap.data_envio_orcamento IS NULL THEN NULL
            WHEN TIME(ap.data_envio_orcamento) <> '00:00:00' THEN ap.data_envio_orcamento
            WHEN ap.data_registro IS NOT NULL
                 AND DATE(ap.data_envio_orcamento) = DATE(ap.data_registro)
              THEN TIMESTAMP(DATE(ap.data_envio_orcamento), TIME(ap.data_registro))
            ELSE ap.data_envio_orcamento
          END AS data_evento
   FROM analise_parcial ap
   WHERE ap.operador = ?
     AND ap.data_envio_orcamento IS NOT NULL
     {$whereData}",
  $binds
);


/* =============== REPARO =============== */
/* Início Reparo (usa data_inicio_reparo; se vier 00:00:00, usa TIME(data_registro)) */
$binds = [$operador];
$whereData = "";
if ($data_inicio && $data_fim) {
  $whereData = " AND (
      CASE
        WHEN rp.data_inicio_reparo IS NULL THEN NULL
        WHEN TIME(rp.data_inicio_reparo) <> '00:00:00' THEN rp.data_inicio_reparo
        WHEN rp.data_registro IS NOT NULL AND TIME(rp.data_registro) <> '00:00:00'
          THEN TIMESTAMP(DATE(rp.data_inicio_reparo), TIME(rp.data_registro))
        ELSE rp.data_inicio_reparo
      END
    ) BETWEEN ? AND ? ";
  $binds[] = $data_inicio . " 00:00:00";
  $binds[] = $data_fim    . " 23:59:59";
}
addEvento(
  $parts, $params, $types,
  "SELECT rp.nota_fiscal, 'Reparo' AS setor, rp.razao_social,
          'Início Reparo' AS status, rp.quantidade_parcial AS quantidade,
          CASE
            WHEN rp.data_inicio_reparo IS NULL THEN NULL
            WHEN TIME(rp.data_inicio_reparo) <> '00:00:00' THEN rp.data_inicio_reparo
            WHEN rp.data_registro IS NOT NULL AND TIME(rp.data_registro) <> '00:00:00'
              THEN TIMESTAMP(DATE(rp.data_inicio_reparo), TIME(rp.data_registro))
            ELSE rp.data_inicio_reparo
          END AS data_evento
   FROM reparo_parcial rp
   WHERE rp.operador = ?
     AND rp.data_inicio_reparo IS NOT NULL
     {$whereData}",
  $binds
);


/* Solicitação NF (usa data_solicitacao_nf; se vier 00:00:00, pega TIME(data_registro) do mesmo dia) */
$binds = [$operador];
$whereData = "";
if ($data_inicio && $data_fim) {
  $whereData = " AND (
      CASE
        WHEN rp.data_solicitacao_nf IS NULL THEN NULL
        WHEN TIME(rp.data_solicitacao_nf) <> '00:00:00' THEN rp.data_solicitacao_nf
        WHEN rp.data_registro IS NOT NULL
             AND DATE(rp.data_solicitacao_nf) = DATE(rp.data_registro)
          THEN TIMESTAMP(DATE(rp.data_solicitacao_nf), TIME(rp.data_registro))
        ELSE rp.data_solicitacao_nf
      END
    ) BETWEEN ? AND ? ";
  $binds[] = $data_inicio . " 00:00:00";
  $binds[] = $data_fim    . " 23:59:59";
}
addEvento(
  $parts, $params, $types,
  "SELECT rp.nota_fiscal, 'Reparo' AS setor, rp.razao_social,
          'Solicitação NF' AS status, rp.quantidade_parcial AS quantidade,
          CASE
            WHEN rp.data_solicitacao_nf IS NULL THEN NULL
            WHEN TIME(rp.data_solicitacao_nf) <> '00:00:00' THEN rp.data_solicitacao_nf
            WHEN rp.data_registro IS NOT NULL
                 AND DATE(rp.data_solicitacao_nf) = DATE(rp.data_registro)
              THEN TIMESTAMP(DATE(rp.data_solicitacao_nf), TIME(rp.data_registro))
            ELSE rp.data_solicitacao_nf
          END AS data_evento
   FROM reparo_parcial rp
   WHERE rp.operador = ?
     AND rp.data_solicitacao_nf IS NOT NULL
     {$whereData}",
  $binds
);


/* =============== QUALIDADE =============== */
/* Início Qualidade (se TIME zerado, usa TIME(data_cadastro) do mesmo dia) */
$binds = [$operador];
$whereData = "";
if ($data_inicio && $data_fim) {
  $whereData = " AND (
      CASE
        WHEN qr.data_inicio_qualidade IS NULL THEN NULL
        WHEN TIME(qr.data_inicio_qualidade) <> '00:00:00' THEN qr.data_inicio_qualidade
        WHEN qr.data_cadastro IS NOT NULL
             AND DATE(qr.data_inicio_qualidade) = DATE(qr.data_cadastro)
          THEN TIMESTAMP(DATE(qr.data_inicio_qualidade), TIME(qr.data_cadastro))
        ELSE qr.data_inicio_qualidade
      END
    ) BETWEEN ? AND ? ";
  $binds[] = $data_inicio . " 00:00:00";
  $binds[] = $data_fim    . " 23:59:59";
}
addEvento(
  $parts, $params, $types,
  "SELECT qr.nota_fiscal, 'Qualidade' AS setor, qr.razao_social,
          'Início Qualidade' AS status, qr.quantidade AS quantidade,
          CASE
            WHEN qr.data_inicio_qualidade IS NULL THEN NULL
            WHEN TIME(qr.data_inicio_qualidade) <> '00:00:00' THEN qr.data_inicio_qualidade
            WHEN qr.data_cadastro IS NOT NULL
                 AND DATE(qr.data_inicio_qualidade) = DATE(qr.data_cadastro)
              THEN TIMESTAMP(DATE(qr.data_inicio_qualidade), TIME(qr.data_cadastro))
            ELSE qr.data_inicio_qualidade
          END AS data_evento
   FROM qualidade_registro qr
   WHERE qr.operador = ?
     AND qr.data_inicio_qualidade IS NOT NULL
     {$whereData}",
  $binds
);

/* Envio Expedição (se TIME zerado, usa TIME(data_cadastro) do mesmo dia) */
$binds = [$operador];
$whereData = "";
if ($data_inicio && $data_fim) {
  $whereData = " AND (
      CASE
        WHEN qr.data_envio_expedicao IS NULL THEN NULL
        WHEN TIME(qr.data_envio_expedicao) <> '00:00:00' THEN qr.data_envio_expedicao
        WHEN qr.data_cadastro IS NOT NULL
             AND DATE(qr.data_envio_expedicao) = DATE(qr.data_cadastro)
          THEN TIMESTAMP(DATE(qr.data_envio_expedicao), TIME(qr.data_cadastro))
        ELSE qr.data_envio_expedicao
      END
    ) BETWEEN ? AND ? ";
  $binds[] = $data_inicio . " 00:00:00";
  $binds[] = $data_fim    . " 23:59:59";
}
addEvento(
  $parts, $params, $types,
  "SELECT qr.nota_fiscal, 'Qualidade' AS setor, qr.razao_social,
          'Envio Expedição' AS status, qr.quantidade AS quantidade,
          CASE
            WHEN qr.data_envio_expedicao IS NULL THEN NULL
            WHEN TIME(qr.data_envio_expedicao) <> '00:00:00' THEN qr.data_envio_expedicao
            WHEN qr.data_cadastro IS NOT NULL
                 AND DATE(qr.data_envio_expedicao) = DATE(qr.data_cadastro)
              THEN TIMESTAMP(DATE(qr.data_envio_expedicao), TIME(qr.data_cadastro))
            ELSE qr.data_envio_expedicao
          END AS data_evento
   FROM qualidade_registro qr
   WHERE qr.operador = ?
     AND qr.data_envio_expedicao IS NOT NULL
     {$whereData}",
  $binds
);

/* Registro Qualidade (data_cadastro – já tem horário real se for DATETIME) */
$binds = [$operador];
$whereData = "";
if ($data_inicio && $data_fim) {
  $whereData = " AND qr.data_cadastro BETWEEN ? AND ? ";
  $binds[] = $data_inicio . " 00:00:00";
  $binds[] = $data_fim    . " 23:59:59";
}
addEvento(
  $parts, $params, $types,
  "SELECT qr.nota_fiscal, 'Qualidade' AS setor, qr.razao_social,
          'Registro Qualidade' AS status, qr.quantidade AS quantidade,
          qr.data_cadastro AS data_evento
   FROM qualidade_registro qr
   WHERE qr.operador = ? {$whereData}",
  $binds
);

/* =================== SQL final =================== */
$sql = "
SELECT nota_fiscal, setor, razao_social, status, quantidade, data_evento
FROM (
  " . implode("\nUNION ALL\n", $parts) . "
) AS eventos
WHERE data_evento IS NOT NULL
ORDER BY data_evento DESC, setor ASC, nota_fiscal ASC
";

/* =================== Execução =================== */
$stmt = $conn->prepare($sql);
if (!$stmt) { die("Erro no prepare: " . $conn->error); }

if ($types !== "") {
  $bind = [];
  $bind[] = &$types;
  foreach ($params as $k => $v) { $bind[] = &$params[$k]; }
  if (!call_user_func_array([$stmt, 'bind_param'], $bind)) {
    die("Erro no bind_param");
  }
}

if (!$stmt->execute()) { die("Erro na execução: " . $stmt->error); }
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Eventos — <?= htmlspecialchars($operador) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;padding:20px;color:#222}
    h2{margin:0 0 12px}
    form{margin:10px 0 16px;display:flex;gap:10px;flex-wrap:wrap;align-items:center}
    input[type="date"]{padding:6px 8px;border:1px solid #ccc;border-radius:6px}
    button{padding:10px 14px;background:#007bff;color:#fff;border:none;border-radius:8px;cursor:pointer;display:inline-flex;gap:8px;align-items:center}
    button:hover{background:#0056b3}
    table{border-collapse:collapse;width:100%;margin-top:16px}
    th,td{border:1px solid #e4e4e4;padding:8px 10px;text-align:center}
    th{background:#007bff;color:#fff;position:sticky;top:0}
    tr:nth-child(even){background:#fafafa}
    tr:hover{background:#f0f6ff}
    .muted{color:#666;font-size:12px}
  </style>
</head>
<body>
  <div style="display:flex;justify-content:space-between;gap:10px;align-items:end;flex-wrap:wrap">
    <h2>Eventos — <?= htmlspecialchars($operador) ?></h2>
    <div class="muted">
      <?php if ($data_inicio && $data_fim): ?>
        Período: <?= htmlspecialchars($data_inicio) ?> a <?= htmlspecialchars($data_fim) ?>
      <?php else: ?>
        Período: todos os registros
      <?php endif; ?>
    </div>
  </div>

  <form method="POST">
    <input type="hidden" name="operador" value="<?= htmlspecialchars($operador) ?>">
    <label>Início: <input type="date" name="data_inicio" value="<?= htmlspecialchars($data_inicio) ?>"></label>
    <label>Fim: <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>"></label>
    <button type="submit"><i class="fas fa-filter"></i> Filtrar</button>
    <button type="button" onclick="exportToExcel()"><i class="fas fa-file-excel"></i> Excel</button>
    <button type="button" onclick="exportToPDF()"><i class="fas fa-file-pdf"></i> PDF</button>
  </form>

  <table id="tabela-relatorio">
    <thead>
      <tr>
        <th>NF</th>
        <th>Setor</th>
        <th>Cliente</th>
        <th>Evento</th>
        <th>Qtd</th>
        <th>Data</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result->num_rows === 0): ?>
        <tr><td colspan="6">Sem registros no período selecionado.</td></tr>
      <?php else: ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['nota_fiscal'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['setor'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['razao_social'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['status'] ?? '') ?></td>
            <td><?= (int)($row['quantidade'] ?? 0) ?></td>
            <td><?= !empty($row['data_evento']) ? date('d/m/Y H:i:s', strtotime($row['data_evento'])) : '' ?></td>
          </tr>
        <?php endwhile; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js" defer></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" defer></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js" defer></script>
  <script>
    function exportToExcel() {
      const table = document.getElementById("tabela-relatorio");
      const wb = XLSX.utils.table_to_book(table, {sheet: "Eventos"});
      XLSX.writeFile(wb, "eventos_<?= preg_replace('/\s+/', '_', $operador) ?>.xlsx");
    }
    function exportToPDF() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();
      doc.text("Eventos — <?= $operador ?>", 14, 15);
      doc.autoTable({ html: '#tabela-relatorio', startY: 20 });
      doc.save("eventos_<?= preg_replace('/\s+/', '_', $operador) ?>.pdf");
    }
  </script>
</body>
</html>
