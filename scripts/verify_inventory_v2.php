<?php

declare(strict_types=1);

// Verify inventory v2 data vs legacy tables and basic movements logging.
// Output is plain text for quick inspection.

$envPath = __DIR__.'/../.env';
if (! is_file($envPath)) {
    fwrite(STDERR, ".env not found\n");
    exit(1);
}
$env = file_get_contents($envPath);
if ($env === false) {
    fwrite(STDERR, "Failed to read .env\n");
    exit(1);
}

function envValue(string $env, string $key): ?string
{
    if (! preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $env, $m)) {
        return null;
    }
    $v = trim($m[1]);
    if ($v === '') {
        return null;
    }
    if (($v[0] === '"' && substr($v, -1) === '"') || ($v[0] === "'" && substr($v, -1) === "'")) {
        $v = substr($v, 1, -1);
    }

    return $v;
}

$host = envValue($env, 'DB_HOST') ?? '127.0.0.1';
$port = (int) (envValue($env, 'DB_PORT') ?? '3306');
$db = envValue($env, 'DB_DATABASE') ?? '';
$user = envValue($env, 'DB_USERNAME') ?? '';
$pass = envValue($env, 'DB_PASSWORD') ?? '';

if ($db === '' || $user === '') {
    fwrite(STDERR, "Missing DB_DATABASE or DB_USERNAME in .env\n");
    exit(1);
}

$mysqli = @new mysqli($host, $user, $pass, $db, $port);
if ($mysqli->connect_errno) {
    fwrite(STDERR, 'DB connect failed: '.$mysqli->connect_error."\n");
    exit(1);
}
$mysqli->set_charset('utf8mb4');

echo "== Inventory v2 vs legacy (sample 10 variants) ==\n";

$variantIds = [];
$res = $mysqli->query('SELECT id FROM variants ORDER BY id ASC LIMIT 10');
while ($row = $res->fetch_assoc()) {
    $variantIds[] = (int) $row['id'];
}
if (! $variantIds) {
    echo "No variants\n";
    exit(0);
}

foreach ($variantIds as $vid) {
    $legacyImport = (int) ($mysqli->query("SELECT COALESCE(SUM(qty),0) AS s FROM product_warehouse WHERE variant_id={$vid} AND type='import'")->fetch_assoc()['s'] ?? 0);
    $legacyExport = (int) ($mysqli->query("SELECT COALESCE(SUM(qty),0) AS s FROM product_warehouse WHERE variant_id={$vid} AND type='export'")->fetch_assoc()['s'] ?? 0);
    $legacy = max(0, $legacyImport - $legacyExport);

    $new = (int) ($mysqli->query("SELECT COALESCE(physical_stock,0) AS s FROM inventory_stocks WHERE variant_id={$vid} LIMIT 1")->fetch_assoc()['s'] ?? 0);
    $mov = (int) ($mysqli->query("SELECT COUNT(*) AS c FROM stock_movements WHERE variant_id={$vid}")->fetch_assoc()['c'] ?? 0);

    echo "variant={$vid} legacy={$legacy} new={$new} diff=".($new - $legacy)." movements={$mov}\n";
}

echo "\n== Last 10 movements ==\n";
$res2 = $mysqli->query('SELECT id, variant_id, warehouse_id, movement_type, quantity, created_at FROM stock_movements ORDER BY id DESC LIMIT 10');
while ($r = $res2->fetch_assoc()) {
    echo "#{$r['id']} v={$r['variant_id']} wh={$r['warehouse_id']} type={$r['movement_type']} qty={$r['quantity']} at={$r['created_at']}\n";
}
