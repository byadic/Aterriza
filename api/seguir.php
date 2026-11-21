<?php
// POST (JSON): { id, accion: 'seguir' | 'dejar' }
require_once __DIR__ . '/../config/db.php';
$u = requiere_sesion();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(false, 'Método no permitido.', [], 405);

$d = leer_json();
$id = (int)($d['id'] ?? 0);
$accion = $d['accion'] ?? 'seguir';
if ($id === $u['id']) responder(false, 'No puedes seguirte a ti mismo.', [], 422);

$st = $pdo->prepare('SELECT 1 FROM usuarios WHERE id = ?');
$st->execute([$id]);
if (!$st->fetchColumn()) responder(false, 'La persona no existe.', [], 404);

if ($accion === 'dejar') {
    $pdo->prepare('DELETE FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?')->execute([$u['id'], $id]);
} else {
    $pdo->prepare('INSERT IGNORE INTO seguidores (seguidor_id, seguido_id) VALUES (?, ?)')->execute([$u['id'], $id]);
}
responder(true, $accion === 'dejar' ? 'Dejaste de seguir.' : 'Ahora la sigues.');
