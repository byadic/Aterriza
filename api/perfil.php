<?php
// GET: devuelve el perfil. POST (JSON): guarda los cambios de "Ajustar perfil".
require_once __DIR__ . '/../config/db.php';
$u = requiere_sesion();

function leer_perfil(PDO $pdo, int $id): array {
    $st = $pdo->prepare('SELECT nombre_completo AS name, edad AS age, procedencia AS city, bio,
                                musica AS music, pasatiempos AS hobbies, comida AS food,
                                plan_favorito AS plan, rol, universidad, carrera,
                                (SELECT COUNT(*) FROM seguidores WHERE seguido_id = usuarios.id) AS seguidores,
                                (SELECT COUNT(*) FROM seguidores WHERE seguidor_id = usuarios.id) AS seguidos
                         FROM usuarios WHERE id = ?');
    $st->execute([$id]);
    $p = $st->fetch() ?: [];
    foreach (['age', 'city', 'bio', 'music', 'hobbies', 'food', 'plan'] as $k) {
        if (($p[$k] ?? null) === null) $p[$k] = '';
    }
    return $p;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = leer_json();
    $campos = ['name' => 100, 'city' => 150, 'bio' => 1000, 'music' => 150, 'hobbies' => 255, 'food' => 150, 'plan' => 80];
    $v = [];
    foreach ($campos as $k => $max) {
        $v[$k] = trim((string)($d[$k] ?? ''));
        if (mb_strlen($v[$k]) > $max) responder(false, 'Hay campos demasiado largos.', [], 422);
    }
    if ($v['name'] === '') responder(false, 'El nombre no puede estar vacío.', [], 422);

    $edad = trim((string)($d['age'] ?? ''));
    if ($edad === '') {
        $edad = null;
    } else {
        $edad = filter_var($edad, FILTER_VALIDATE_INT);
        if ($edad === false || $edad < 18 || $edad > 120) responder(false, 'Ingresa una edad válida (mínimo 18).', [], 422);
    }

    $pdo->prepare('UPDATE usuarios SET nombre_completo = ?, edad = ?, procedencia = ?, bio = ?,
                   musica = ?, pasatiempos = ?, comida = ?, plan_favorito = ? WHERE id = ?')
        ->execute([$v['name'], $edad, $v['city'] ?: null, $v['bio'] ?: null, $v['music'] ?: null,
                   $v['hobbies'] ?: null, $v['food'] ?: null, $v['plan'] ?: null, $u['id']]);
    $_SESSION['nombre'] = $v['name'];
    responder(true, 'Perfil guardado.', ['perfil' => leer_perfil($pdo, $u['id'])]);
}

responder(true, '', ['perfil' => leer_perfil($pdo, $u['id'])]);
