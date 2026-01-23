<?php
require_once __DIR__ . '/../../BackEnd/helpers.php';
require_once __DIR__ . '/../../BackEnd/Database.php';

verificarSessao();
definirHeadersSeguranca();

$db = getDb();

// Busca remessas aguardando_pg e sem armario
$remessas = $db->fetchAll("SELECT id, codigo_remessa, cliente_nome FROM resumo_geral WHERE status = 'aguardando_pg' AND (armario_id IS NULL OR armario_id = '') ORDER BY id DESC LIMIT 500");
$armarios = $db->fetchAll('SELECT id, codigo, descricao FROM armarios WHERE ativo = 1 ORDER BY codigo ASC');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Atribuir Armário - KPI</title>
    <?php echo metaCSRF(); ?>
</head>
<body>
<div class="container">
    <h1>Atribuir Armário (Carga Inicial)</h1>

    <form method="post" action="/BackEnd/Inventario/AtribuirArmario.php" id="atribuirForm">
        <?php echo campoCSRF(); ?>
        <div>
            <label>Escolha o armário</label>
            <select name="armario_id" required>
                <option value="">-- selecione --</option>
                <?php foreach ($armarios as $a): ?>
                    <option value="<?php echo (int)$a['id']; ?>"><?php echo htmlspecialchars($a['codigo'] . ' - ' . $a['descricao']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <h3>Selecione remessas</h3>
            <p>Itens listados: <?php echo count($remessas); ?></p>
            <div style="max-height:400px;overflow:auto;border:1px solid #ddd;padding:8px;">
                <?php foreach ($remessas as $r): ?>
                    <label style="display:block;margin-bottom:6px;">
                        <input type="checkbox" name="remessas[]" value="<?php echo (int)$r['id']; ?>"> 
                        <?php echo htmlspecialchars($r['codigo_remessa'] . ' — ' . ($r['cliente_nome'] ?? '')); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <button class="btn btn-primary" type="submit">Atribuir selecionados</button>
        </div>
    </form>
</div>
</body>
</html>
