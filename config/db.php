<?php

function getEnvironmentValue(string $name): ?string
{
    $value = getenv($name);
    return $value === false || $value === '' ? null : $value;
}

$databaseUrl = getEnvironmentValue('DATABASE_URL');
$urlConfig = [];

if ($databaseUrl !== null) {
    $parts = parse_url($databaseUrl);
    if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
        throw new RuntimeException('DATABASE_URL no es una URL válida.');
    }

    if (!in_array(strtolower($parts['scheme']), ['mysql', 'mysql2'], true)) {
        throw new RuntimeException('DATABASE_URL debe usar el esquema mysql://.');
    }

    $database = rawurldecode(ltrim($parts['path'] ?? '', '/'));
    if ($database === '') {
        throw new RuntimeException('DATABASE_URL debe incluir el nombre de la base de datos.');
    }

    $urlConfig = [
        'host' => $parts['host'],
        'name' => $database,
        'user' => rawurldecode($parts['user'] ?? ''),
        'pass' => rawurldecode($parts['pass'] ?? ''),
        'port' => (string) ($parts['port'] ?? 3306),
    ];
}

$dbHost = getEnvironmentValue('DB_HOST') ?? getEnvironmentValue('MYSQLHOST') ?? $urlConfig['host'] ?? 'localhost';
$dbName = getEnvironmentValue('DB_NAME') ?? getEnvironmentValue('MYSQLDATABASE') ?? $urlConfig['name'] ?? 'aterriza';
$dbUser = getEnvironmentValue('DB_USER') ?? getEnvironmentValue('MYSQLUSER') ?? $urlConfig['user'] ?? 'root';
$dbPass = getEnvironmentValue('DB_PASS') ?? getEnvironmentValue('MYSQLPASSWORD') ?? $urlConfig['pass'] ?? '';
$dbPort = getEnvironmentValue('DB_PORT') ?? getEnvironmentValue('MYSQLPORT') ?? $urlConfig['port'] ?? '3306';

$pdo = new PDO(
    "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
