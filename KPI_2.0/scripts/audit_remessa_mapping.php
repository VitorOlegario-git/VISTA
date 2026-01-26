<?php
/**
 * READ-ONLY AUDIT — NO WRITES
 * Auditoria de mapeamento: codigo_remessa -> resumo_id
 * Gera CSVs:
 *  - audit_map.csv
 *  - audit_duplicates.csv
 *  - audit_norm_collisions.csv
 *  - audit_empty.csv
 */

require_once __DIR__ . '/../BackEnd/Database.php';

/**
 * Obtém conexão somente leitura:
 * 1) tenta getDb() (mysqli wrapper do projeto)
 * 2) fallback para PDO
 */
function getDbReadOnly() {
    // 1) Tenta o wrapper do projeto
    try {
        if (function_exists('getDb')) {
            return getDb(); // pode lançar erro se mysqli não existir
        }
    } catch (Throwable $e) {
        // fallback para PDO
    }

    // 2) Fallback PDO
    if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USERNAME') || !defined('DB_PASSWORD')) {
        fwrite(STDERR, "DB constants not defined (DB_HOST/DB_NAME/DB_USERNAME/DB_PASSWORD)\n");
        exit(1);
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_NAME
    );

    try {
        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (Throwable $e) {
        fwrite(STDERR, "DB connection failed using mysqli and PDO\n");
        exit(1);
    }
}

$db = getDbReadOnly();

// --- Fetch base rows (READ-ONLY) ---
$sql = "SELECT id AS resumo_id, codigo_remessa FROM resumo_geral ORDER BY codigo_remessa, id";

if ($db instanceof PDO) {
    $stmt = $db->query($sql);
    $rows = $stmt->fetchAll();
} else {
    // wrapper do projeto (mysqli)
    $rows = $db->fetchAll($sql);
}

// --- Aggregates ---
$byCode = [];     // duplicidades exatas
$byNorm = [];     // colisões por normalização (trim/lower)
$empty  = [];     // vazios / nulos

foreach ($rows as $r) {
    $id   = (int)$r['resumo_id'];
    $code = $r['codigo_remessa'];

    if ($code === null || trim($code) === '') {
        $empty[] = [$id, $code];
        continue;
    }

    // Duplicidade exata
    if (!isset($byCode[$code])) $byCode[$code] = [];
    $byCode[$code][] = $id;

    // Normalização (case/whitespace)
    $norm = mb_strtolower(trim($code));
    if (!isset($byNorm[$norm])) $byNorm[$norm] = [];
    $byNorm[$norm][] = [$id, $code];
}

// --- Write CSVs ---
$outDir = __DIR__;

// 1) Mapa completo
$mapCsv = fopen($outDir . '/audit_map.csv', 'w');
fputcsv($mapCsv, ['resumo_id', 'codigo_remessa']);
foreach ($rows as $r) {
    fputcsv($mapCsv, [$r['resumo_id'], $r['codigo_remessa']]);
}
fclose($mapCsv);

// 2) Duplicidades exatas
$dupCsv = fopen($outDir . '/audit_duplicates.csv', 'w');
fputcsv($dupCsv, ['codigo_remessa', 'qtd', 'resumo_ids']);
$dupCount = 0;
foreach ($byCode as $code => $ids) {
    if (count($ids) > 1) {
        $dupCount++;
        fputcsv($dupCsv, [$code, count($ids), implode(',', $ids)]);
    }
}
fclose($dupCsv);

// 3) Colisões por normalização
$normCsv = fopen($outDir . '/audit_norm_collisions.csv', 'w');
fputcsv($normCsv, ['codigo_norm', 'qtd', 'resumo_ids', 'valores_originais']);
$normCount = 0;
foreach ($byNorm as $norm => $pairs) {
    if (count($pairs) > 1) {
        $normCount++;
        $ids  = array_map(fn($p) => $p[0], $pairs);
        $vals = array_map(fn($p) => $p[1], $pairs);
        fputcsv($normCsv, [$norm, count($pairs), implode(',', $ids), implode(' | ', $vals)]);
    }
}
fclose($normCsv);

// 4) Vazios / nulos
$emptyCsv = fopen($outDir . '/audit_empty.csv', 'w');
fputcsv($emptyCsv, ['resumo_id', 'codigo_remessa']);
foreach ($empty as $e) {
    fputcsv($emptyCsv, $e);
}
fclose($emptyCsv);

// --- Summary ---
echo "AUDIT SUMMARY\n";
echo "Total registros: " . count($rows) . PHP_EOL;
echo "Duplicidades exatas: " . $dupCount . PHP_EOL;
echo "Colisoes normalizadas: " . $normCount . PHP_EOL;
echo "Vazios/nulos: " . count($empty) . PHP_EOL;
echo PHP_EOL;
echo "Arquivos gerados em {$outDir}:\n";
echo "- audit_map.csv\n";
echo "- audit_duplicates.csv\n";
echo "- audit_norm_collisions.csv\n";
echo "- audit_empty.csv\n";

