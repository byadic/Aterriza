<?php
// GET: tus lugares favoritos. POST (JSON): { accion:'agregar', nombre, zona, nota } o { accion:'eliminar', id }
require_once __DIR__ . '/../config/db.php';
$u = requiere_sesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = leer_json();
    if (($d['accion'] ?? '') === 'eliminar') {
        $pdo->prepare('DELETE FROM lugares_favoritos WHERE id = ? AND usuario_id = ?')->execute([(int)($d['id'] ?? 0), $u['id']]);
        responder(true, 'Lugar eliminado.');
    }
    $nombre = trim($d['nombre'] ?? ''); $zona = trim($d['zona'] ?? ''); $nota = trim($d['nota'] ?? '');
    if ($nombre === '') responder(false, 'Escribe el nombre del lugar.', [], 422);
    if (mb_strlen($nombre) > 120 || mb_strlen($zona) > 120 || mb_strlen($nota) > 255) responder(false, 'Hay campos demasiado largos.', [], 422);
    $pdo->prepare('INSERT INTO lugares_favoritos (usuario_id, nombre, zona, nota) VALUES (?, ?, ?, ?)')
        ->execute([$u['id'], $nombre, $zona ?: null, $nota ?: null]);
    responder(true, 'Lugar guardado.');
}

$st = $pdo->prepare('SELECT id, nombre, zona, nota FROM lugares_favoritos WHERE usuario_id = ? ORDER BY id DESC');
$st->execute([$u['id']]);
responder(true, '', ['lugares' => $st->fetchAll()]);
