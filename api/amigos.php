<?php
// GET: lista de amigos con su último mensaje.
// POST (JSON): { busqueda, mensaje } añade un amigo por correo o nombre.
require_once __DIR__ . '/../config/db.php';
$u = requiere_sesion();
$yo = $u['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = leer_json();
    $busqueda = trim($d['busqueda'] ?? '');
    $mensaje  = mb_substr(trim($d['mensaje'] ?? ''), 0, 1000);
    if (!empty($d['id'])) {   // desde una tarjeta de persona
        $st = $pdo->prepare('SELECT id FROM usuarios WHERE id = ? AND id <> ?');
        $st->execute([(int)$d['id'], $yo]);
    } else {
        if ($busqueda === '') responder(false, 'Escribe el nombre o correo de la persona.', [], 422);
        $st = $pdo->prepare('SELECT id FROM usuarios WHERE id <> ? AND (correo = ? OR nombre_completo LIKE ?) LIMIT 2');
        $st->execute([$yo, strtolower($busqueda), '%' . $busqueda . '%']);
    }
    $res = $st->fetchAll();
    if (count($res) === 0) responder(false, 'No se encontró a nadie con ese nombre o correo.', [], 404);
    if (count($res) > 1)  responder(false, 'Hay varias personas con ese nombre. Usa su correo.', [], 409);

    $amigo = (int)$res[0]['id'];
    [$a, $b] = $yo < $amigo ? [$yo, $amigo] : [$amigo, $yo];
    $pdo->prepare('INSERT IGNORE INTO amistades (usuario_a, usuario_b) VALUES (?, ?)')->execute([$a, $b]);
    if ($mensaje !== '') {
        $pdo->prepare('INSERT INTO mensajes (remitente_id, destinatario_id, texto) VALUES (?, ?, ?)')->execute([$yo, $amigo, $mensaje]);
    }
    responder(true, 'Amigo añadido.');
}

$st = $pdo->prepare(
    'SELECT u.id, u.nombre_completo AS nombre,
            (SELECT m.texto FROM mensajes m
              WHERE (m.remitente_id = ? AND m.destinatario_id = u.id)
                 OR (m.remitente_id = u.id AND m.destinatario_id = ?)
              ORDER BY m.id DESC LIMIT 1) AS ultimo
     FROM amistades a
     JOIN usuarios u ON u.id = IF(a.usuario_a = ?, a.usuario_b, a.usuario_a)
     WHERE a.usuario_a = ? OR a.usuario_b = ?
     ORDER BY u.nombre_completo'
);
$st->execute([$yo, $yo, $yo, $yo, $yo]);
responder(true, '', ['amigos' => $st->fetchAll()]);
