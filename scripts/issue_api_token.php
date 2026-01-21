<?php

declare(strict_types=1);

// Minimal helper: fetch or create a user's api_token without bootstrapping Laravel.
// Uses .env DB_* settings and updates users.api_token if empty.

$envPath = __DIR__ . '/../.env';
if (!is_file($envPath)) {
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
    if (!preg_match('/^' . preg_quote($key, '/') . '=(.*)$/m', $env, $m)) {
        return null;
    }
    $v = trim($m[1]);
    if ($v === '') {
        return null;
    }
    // Strip surrounding quotes
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
    fwrite(STDERR, "DB connect failed: " . $mysqli->connect_error . "\n");
    exit(1);
}

$mysqli->set_charset('utf8mb4');

// Find a user (prefer one that already has api_token)
$sql = "SELECT id, api_token FROM users ORDER BY (api_token IS NOT NULL AND api_token <> '') DESC, id ASC LIMIT 1";
$res = $mysqli->query($sql);
if (!$res) {
    fwrite(STDERR, "Query failed: " . $mysqli->error . "\n");
    exit(1);
}
$row = $res->fetch_assoc();
if (!$row) {
    fwrite(STDERR, "No users found\n");
    exit(1);
}

$id = (int) $row['id'];
$token = (string) ($row['api_token'] ?? '');

if ($token === '') {
    $token = bin2hex(random_bytes(20));
    $stmt = $mysqli->prepare("UPDATE users SET api_token = ? WHERE id = ?");
    if (!$stmt) {
        fwrite(STDERR, "Prepare failed: " . $mysqli->error . "\n");
        exit(1);
    }
    $stmt->bind_param('si', $token, $id);
    if (!$stmt->execute()) {
        fwrite(STDERR, "Update failed: " . $stmt->error . "\n");
        exit(1);
    }
    $stmt->close();
}

echo $token;


