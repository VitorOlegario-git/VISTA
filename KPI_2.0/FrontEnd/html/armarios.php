<?php
require_once __DIR__ . '/../../BackEnd/helpers.php';
require_once __DIR__ . '/../../BackEnd/Database.php';

verificarSessao();
definirHeadersSeguranca();

$db = getDb();
$armarios = $db->fetchAll('SELECT * FROM armarios ORDER BY codigo ASC');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Armários - KPI</title>
    <?php echo metaCSRF(); ?>
</head>
<body>
<div class="container">
    <h1>Armários</h1>

    <section class="card">
        <h2>Cadastrar novo armário</h2>
        <form method="post" action="/BackEnd/Inventario/Armario.php">
            <?php echo campoCSRF(); ?>
            <div>
                <label>Código</label>
                <input name="codigo" required maxlength="64">
            </div>
            <div>
                <label>Descrição</label>
                <input name="descricao" maxlength="255">
            </div>
            <div>
                <label><input type="checkbox" name="ativo" checked> Ativo</label>
            </div>
            <div>
                <button class="btn btn-primary" type="submit">Salvar</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>Lista</h2>
        <table class="table">
            <thead><tr><th>Código</th><th>Descrição</th><th>Ativo</th></tr></thead>
            <tbody>
            <?php foreach ($armarios as $a): ?>
                <tr>
                    <td><?php echo htmlspecialchars($a['codigo']); ?></td>
                    <td><?php echo htmlspecialchars($a['descricao']); ?></td>
                    <td><?php echo $a['ativo'] ? 'Sim' : 'Não'; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
</body>
</html>
