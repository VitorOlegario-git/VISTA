<?php
session_start();

require_once __DIR__ . '/../BackEnd/helpers.php';
require_once __DIR__ . '/../BackEnd/config.php';

// Validação de acesso: apenas "Vitor Olegario"
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'Vitor Olegario') {
    http_response_code(403);
    echo "Acesso restrito";
    exit();
}

// Conexão com o banco (ajuste conforme seu ambiente)
try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo "Erro ao conectar ao banco de dados.";
    exit();
}

// Filtros de busca
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Monta SQL seguro
$sql = "SELECT id, razao_social, nota_fiscal, quantidade_pecas FROM remessas WHERE 1=1";
$params = [];

if ($status !== '') {
    $sql .= " AND status = :status";
    $params[':status'] = $status;
}
if ($search !== '') {
    $sql .= " AND (razao_social LIKE :search OR nota_fiscal LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $remessas = $stmt->fetchAll();
} catch (Exception $e) {
    http_response_code(500);
    echo "Erro ao consultar remessas.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Status de Inventário</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>Status de Inventário</h1>
    <form method="get">
        <label>Status:
            <select name="status">
                <option value="">Todos</option>
                <option value="pendente" <?= $status === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                <option value="concluido" <?= $status === 'concluido' ? 'selected' : '' ?>>Concluído</option>
                <!-- Adicione outros status conforme necessário -->
            </select>
        </label>
        <label>Buscar:
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>">
        </label>
        <button type="submit">Filtrar</button>
    </form>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Razão Social</th>
                <th>Nota Fiscal</th>
                <th>Quantidade de Peças</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($remessas)): ?>
                <tr><td colspan="4">Nenhuma remessa encontrada.</td></tr>
            <?php else: ?>
                <?php foreach ($remessas as $remessa): ?>
                    <tr>
                        <td><?= htmlspecialchars($remessa['id']) ?></td>
                        <td><?= htmlspecialchars($remessa['razao_social']) ?></td>
                        <td><?= htmlspecialchars($remessa['nota_fiscal']) ?></td>
                        <td><?= htmlspecialchars($remessa['quantidade_pecas']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
