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

function responder(bool $ok, string $mensaje, array $extra = [], int $codigo = 200): void
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'mensaje' => $mensaje], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function leer_json(): array
{
    $datos = json_decode(file_get_contents('php://input'), true);
    return is_array($datos) ? $datos : [];
}

function requiere_sesion(): array
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['usuario_id'])) responder(false, 'Debes iniciar sesión.', [], 401);
    return ['id' => (int) $_SESSION['usuario_id'], 'nombre' => $_SESSION['nombre'], 'rol' => $_SESSION['rol']];
}

function son_amigos(PDO $pdo, int $a, int $b): bool
{
    [$x, $y] = $a < $b ? [$a, $b] : [$b, $a];
    $st = $pdo->prepare('SELECT 1 FROM amistades WHERE usuario_a = ? AND usuario_b = ?');
    $st->execute([$x, $y]);
    return (bool) $st->fetchColumn();
}

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    responder(false, 'No se pudo conectar con la base de datos.', [], 500);
}
