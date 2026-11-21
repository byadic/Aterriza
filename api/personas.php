<?php
// GET ?q=intereses&ciudad=...&rol=foraneo|residente : busca personas con filtros.
require_once __DIR__ . '/../config/db.php';
$u = requiere_sesion();
$yo = $u['id'];

$sql = "SELECT us.id, us.nombre_completo AS nombre, us.rol, us.procedencia AS ciudad, us.universidad, us.carrera, us.bio,
               us.musica, us.pasatiempos, us.comida, us.plan_favorito AS plan,
               (SELECT COUNT(*) FROM seguidores s WHERE s.seguidor_id = ? AND s.seguido_id = us.id) AS sigo,
               (SELECT COUNT(*) FROM seguidores s WHERE s.seguido_id = us.id) AS seguidores
        FROM usuarios us WHERE us.id <> ?";
$par = [$yo, $yo];
$rol = $_GET['rol'] ?? '';
$ciudad = trim($_GET['ciudad'] ?? '');
$q = trim($_GET['q'] ?? '');
if (in_array($rol, ['foraneo', 'residente'], true)) { $sql .= ' AND us.rol = ?'; $par[] = $rol; }
if ($ciudad !== '') { $sql .= ' AND us.procedencia LIKE ?'; $par[] = "%$ciudad%"; }
if ($q !== '') {
    $sql .= " AND CONCAT_WS(' ', us.musica, us.pasatiempos, us.comida, us.plan_favorito, us.bio, us.carrera) LIKE ?";
    $par[] = "%$q%";
}
$st = $pdo->prepare($sql . ' ORDER BY us.id DESC LIMIT 60');
$st->execute($par);
$gente = $st->fetchAll();

// Mis intereses, para calcular coincidencias
$st = $pdo->prepare("SELECT CONCAT_WS(',', musica, pasatiempos, comida, plan_favorito) FROM usuarios WHERE id = ?");
$st->execute([$yo]);
$mios = array_filter(array_map('trim', preg_split('/[,;\/]+/', mb_strtolower((string)$st->fetchColumn()))), fn($t) => mb_strlen($t) >= 3);

// Mis amigos
$st = $pdo->prepare('SELECT IF(usuario_a = ?, usuario_b, usuario_a) FROM amistades WHERE usuario_a = ? OR usuario_b = ?');
$st->execute([$yo, $yo, $yo]);
$amigos = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));

foreach ($gente as &$g) {
    $texto = mb_strtolower(implode(',', [$g['musica'], $g['pasatiempos'], $g['comida'], $g['plan']]));
    $g['comunes'] = count(array_filter($mios, fn($t) => str_contains($texto, $t)));
    $g['intereses'] = array_values(array_filter([$g['musica'], $g['pasatiempos'], $g['comida'], $g['plan']]));
    $g['sigo'] = (bool)$g['sigo']; $g['seguidores'] = (int)$g['seguidores'];
    $g['amigo'] = in_array((int)$g['id'], $amigos, true); $g['id'] = (int)$g['id'];
    unset($g['musica'], $g['pasatiempos'], $g['comida'], $g['plan']);
}
unset($g);
usort($gente, fn($a, $b) => $b['comunes'] <=> $a['comunes']);
responder(true, '', ['personas' => $gente]);
