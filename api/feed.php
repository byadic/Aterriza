<?php
// GET ?tipo=ayuda|plan|recomendacion|otro (opcional): publicaciones de toda la comunidad.
require_once __DIR__ . '/../config/db.php';
$u = requiere_sesion();

$sql = 'SELECT p.id, p.tipo, p.descripcion AS texto, p.ruta_imagen AS src, p.fecha,
               us.id AS usuario_id, us.nombre_completo AS nombre, us.rol,
               (SELECT COUNT(*) FROM seguidores s WHERE s.seguidor_id = ? AND s.seguido_id = us.id) AS sigo
        FROM publicaciones p JOIN usuarios us ON us.id = p.usuario_id';
$par = [$u['id']];
$tipo = $_GET['tipo'] ?? '';
if (in_array($tipo, ['ayuda', 'plan', 'recomendacion', 'otro'], true)) { $sql .= ' WHERE p.tipo = ?'; $par[] = $tipo; }
$st = $pdo->prepare($sql . ' ORDER BY p.id DESC LIMIT 50');
$st->execute($par);
$filas = array_map(function ($r) {
    $r['usuario_id'] = (int)$r['usuario_id']; $r['sigo'] = (bool)$r['sigo']; return $r;
}, $st->fetchAll());
responder(true, '', ['feed' => $filas]);
