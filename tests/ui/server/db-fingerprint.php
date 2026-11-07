<?php
declare(strict_types=1);

/**
 * Deterministic content fingerprint for one runner-owned database.
 *
 * It reads only table names and MariaDB `CHECKSUM TABLE` results, so it never
 * exposes row data. The seeded instance is fingerprinted before and after the
 * installer selection to prove the wizard did not write to it.
 */

$host = getenv('SP_UI_FP_DB_HOST') ?: 'db';
$port = (int)(getenv('SP_UI_FP_DB_PORT') ?: 3306);
$user = getenv('SP_UI_FP_DB_USER') ?: 'root';
$password = getenv('SP_UI_FP_DB_PASSWORD') ?: '';
$database = getenv('SP_UI_FP_DB_NAME') ?: '';

if ($password === '' || $database === '') {
    fwrite(STDERR, "Database fingerprint requires SP_UI_FP_DB_PASSWORD and SP_UI_FP_DB_NAME\n");

    exit(1);
}

try {
    $connection = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s', $host, $port, $database),
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]
    );

    $tables = $connection
        ->query('SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY table_name')
        ->fetchAll(PDO::FETCH_COLUMN);

    $checksums = [];

    foreach ($tables as $table) {
        $statement = $connection->query(sprintf('CHECKSUM TABLE `%s`', str_replace('`', '``', $table)));
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        $checksums[(string)$table] = (string)($row['Checksum'] ?? '');
    }
} catch (PDOException $e) {
    fwrite(STDERR, "Database fingerprint failed\n");

    exit(1);
}

$lines = [];

foreach ($checksums as $table => $checksum) {
    $lines[] = $table . ':' . $checksum;
}

echo json_encode(
    [
        'ok' => true,
        'database' => $database,
        'digest' => hash('sha256', implode("\n", $lines)),
        'tables' => $checksums,
    ],
    JSON_THROW_ON_ERROR
), PHP_EOL;
