<?php
// GET ?amigo_id=N: mensajes de la conversación. POST (JSON): { amigo_id, texto }.
require_once __DIR__ . '/../config/db.php';
$u = requiere_sesion();
$yo = $u['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = leer_json();
    $amigo = (int)($d['amigo_id'] ?? 0);
    $texto = mb_substr(trim($d['texto'] ?? ''), 0, 1000);
    if ($texto === '') responder(false, 'Escribe un mensaje.', [], 422);
    if (!son_amigos($pdo, $yo, $amigo)) responder(false, 'Solo puedes escribir a tus amigos.', [], 403);
    $pdo->prepare('INSERT INTO mensajes (remitente_id, destinatario_id, texto) VALUES (?, ?, ?)')->execute([$yo, $amigo, $texto]);
    responder(true, 'Enviado.');
}

$amigo = (int)($_GET['amigo_id'] ?? 0);
if (!son_amigos($pdo, $yo, $amigo)) responder(false, 'Conversación no disponible.', [], 403);

$st = $pdo->prepare(
    'SELECT texto, (remitente_id = ?) AS yo FROM mensajes
     WHERE (remitente_id = ? AND destinatario_id = ?) OR (remitente_id = ? AND destinatario_id = ?)
     ORDER BY id ASC LIMIT 200'
);
$st->execute([$yo, $yo, $amigo, $amigo, $yo]);
$mensajes = array_map(fn($m) => ['texto' => $m['texto'], 'yo' => (bool)$m['yo']], $st->fetchAll());
responder(true, '', ['mensajes' => $mensajes]);
