<?php
declare(strict_types=1);

function read_sql_file(string $path): string {
    if (!file_exists($path)) {
        throw new RuntimeException("SQL file not found: $path");
    }
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException("Failed to read SQL file: $path");
    }
    return $sql;
}

$root = dirname(__DIR__);
$varDir = $root . '/var';
$dbPath = $varDir . '/app.sqlite';

@mkdir($varDir, 0775, true);

$isNew = !file_exists($dbPath);

$pdo = new PDO('sqlite:' . $dbPath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// Enforce FK constraints in SQLite
$pdo->exec('PRAGMA foreign_keys = ON;');

if (!$isNew) {
    echo "Database already exists at {$dbPath}\n";
    echo "If you want to reset it, delete var/app.sqlite and re-run.\n";
    exit(0);
}

echo "Creating SQLite DB at {$dbPath}\n";

$schemaSql = trim(read_sql_file($root . '/database/schema.sql'));
$seedSql   = trim(read_sql_file($root . '/database/seed.sql'));

if ($schemaSql === '') {
    @unlink($dbPath);
    throw new RuntimeException("database/schema.sql is empty.");
}

if ($seedSql === '') {
    @unlink($dbPath);
    throw new RuntimeException("database/seed.sql is empty.");
}

$pdo->beginTransaction();
try {
    $pdo->exec($schemaSql);
    $pdo->exec($seedSql);
    $pdo->commit();
    echo "Initialized schema + seed data.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    // Remove partially-created DB
    @unlink($dbPath);
    throw $e;
}