<?php
session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'foraneo') {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../config/db.php';
$usuarioId = (int) $_SESSION['usuario_id'];
$interesesFiltro = isset($_GET['intereses']) && is_string($_GET['intereses'])
    ? trim($_GET['intereses'])
    : '';
$ciudadFiltro = isset($_GET['ciudad_destino']) && is_string($_GET['ciudad_destino'])
    ? trim($_GET['ciudad_destino'])
    : '';
$rolFiltro = isset($_GET['rol']) && is_string($_GET['rol']) ? $_GET['rol'] : '';
if (!in_array($rolFiltro, ['', 'guia', 'foraneo'], true)) {
    $rolFiltro = '';
}

$stmt = $pdo->prepare('SELECT ciudad_destino, intereses FROM usuarios WHERE id = ?');
$stmt->execute([$usuarioId]);
$usuarioActual = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$usuarioActual) {
    http_response_code(403);
    exit('No se encontró el usuario de la sesión.');
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'conectar') {
    $destinatarioId = filter_input(INPUT_POST, 'destinatario_id', FILTER_VALIDATE_INT);
    if (!$destinatarioId || $destinatarioId === $usuarioId) {
        $mensaje = 'No se pudo enviar la solicitud.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE id = ? AND id <> ?');
        $stmt->execute([$destinatarioId, $usuarioId]);
        if (!$stmt->fetchColumn()) {
            $mensaje = 'La persona seleccionada no está disponible.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT id FROM conexiones
                 WHERE (solicitante_id = ? AND destinatario_id = ?)
                    OR (solicitante_id = ? AND destinatario_id = ?)
                 LIMIT 1'
            );
            $stmt->execute([$usuarioId, $destinatarioId, $destinatarioId, $usuarioId]);
            if ($stmt->fetchColumn()) {
                $mensaje = 'Ya existe una solicitud o conexión con esta persona.';
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO conexiones (solicitante_id, destinatario_id, estado)
                     VALUES (?, ?, 'pendiente')"
                );
                $stmt->execute([$usuarioId, $destinatarioId]);
                $parametros = [
                    'intereses' => $interesesFiltro,
                    'ciudad_destino' => $ciudadFiltro,
                    'rol' => $rolFiltro
                ];
                header('Location: explorar_foraneo.php?' . http_build_query($parametros));
                exit;
            }
        }
    }
}

$sql = 'SELECT id, nombre, rol, ciudad_origen, ciudad_destino, carrera, intereses, whatsapp
        FROM usuarios WHERE id <> ?';
$params = [$usuarioId];
if ($rolFiltro !== '') {
    $sql .= ' AND rol = ?';
    $params[] = $rolFiltro;
}
if ($interesesFiltro !== '') {
    $sql .= ' AND intereses LIKE ?';
    $params[] = '%' . $interesesFiltro . '%';
}
if ($ciudadFiltro !== '') {
    $sql .= ' AND ciudad_destino LIKE ?';
    $params[] = '%' . $ciudadFiltro . '%';
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$personas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    'SELECT solicitante_id, destinatario_id, estado
     FROM conexiones
     WHERE solicitante_id = ? OR destinatario_id = ?'
);
$stmt->execute([$usuarioId, $usuarioId]);
$estados = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $conexion) {
    $otraPersonaId = (int) $conexion['solicitante_id'] === $usuarioId
        ? (int) $conexion['destinatario_id']
        : (int) $conexion['solicitante_id'];
    if (!isset($estados[$otraPersonaId]) || $conexion['estado'] === 'aceptada') {
        $estados[$otraPersonaId] = $conexion['estado'];
    }
}

$interesesActuales = array_filter(array_map(
    static fn($interes) => strtolower(trim($interes)),
    explode(',', $usuarioActual['intereses'])
));
foreach ($personas as &$persona) {
    $interesesPersona = array_filter(array_map(
        static fn($interes) => strtolower(trim($interes)),
        explode(',', $persona['intereses'])
    ));
    $persona['coincidencias'] = count(array_intersect($interesesActuales, $interesesPersona));
    $persona['misma_ciudad'] = strcasecmp(
        trim($usuarioActual['ciudad_destino']),
        trim($persona['ciudad_destino'])
    ) === 0;
    $persona['puntaje'] = $persona['coincidencias'] + ($persona['misma_ciudad'] ? 2 : 0);
    $persona['estado_conexion'] = $estados[(int) $persona['id']] ?? '';
}
unset($persona);
usort($personas, static function ($a, $b) {
    return ($b['puntaje'] <=> $a['puntaje']) ?: strcasecmp($a['nombre'], $b['nombre']);
});

$esc = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Personas y Guías | Aterriza</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
    <a href="panel_foraneo.php"><strong>Aterriza</strong></a>
    <div>
        <a href="explorar_foraneo.php">Personas y Guías</a>
        <a href="cuartos_foraneo.php">Cuartos Disponibles</a>
        <a href="panel_foraneo.php">Mi Panel</a>
    </div>
</nav>
<main class="page-wide">
    <h1>Personas y Guías</h1>
    <form class="filters" method="get">
        <div>
            <label for="intereses">Intereses</label>
            <input id="intereses" name="intereses" value="<?= $esc($interesesFiltro) ?>" placeholder="Ej. música">
        </div>
        <div>
            <label for="ciudad_destino">Ciudad de destino</label>
            <input id="ciudad_destino" name="ciudad_destino" value="<?= $esc($ciudadFiltro) ?>" placeholder="Ej. Ciudad de México">
        </div>
        <div>
            <label for="rol">Ver</label>
            <select id="rol" name="rol">
                <option value="" <?= $rolFiltro === '' ? 'selected' : '' ?>>Todos</option>
                <option value="guia" <?= $rolFiltro === 'guia' ? 'selected' : '' ?>>Solo Guías</option>
                <option value="foraneo" <?= $rolFiltro === 'foraneo' ? 'selected' : '' ?>>Solo Foráneos</option>
            </select>
        </div>
        <button type="submit">Filtrar</button>
    </form>

    <?php if ($mensaje): ?><p class="error"><?= $esc($mensaje) ?></p><?php endif; ?>
    <?php if (!$personas): ?>
        <p>No se encontraron personas con esos filtros.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($personas as $persona): ?>
                <?php $whatsapp = preg_replace('/\D+/', '', $persona['whatsapp']); ?>
                <section class="card">
                    <?php if ($persona['puntaje'] > 0): ?>
                        <p><strong>Coincidencia para ti</strong></p>
                    <?php endif; ?>
                    <h2><?= $esc($persona['nombre']) ?></h2>
                    <p><strong>Rol:</strong> <?= $persona['rol'] === 'guia' ? 'Guía' : 'Foráneo' ?></p>
                    <p><strong>Ciudad:</strong> <?= $esc($persona['ciudad_origen']) ?> · Destino: <?= $esc($persona['ciudad_destino']) ?></p>
                    <p><strong>Carrera:</strong> <?= $esc($persona['carrera']) ?></p>
                    <p><strong>Intereses:</strong> <?= $esc($persona['intereses']) ?></p>
                    <?php if ($persona['estado_conexion'] === 'aceptada'): ?>
                        <a class="button contact" href="https://wa.me/<?= $esc($whatsapp) ?>" target="_blank" rel="noopener">WhatsApp</a>
                    <?php elseif ($persona['estado_conexion'] === 'pendiente'): ?>
                        <p>Solicitud pendiente.</p>
                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="accion" value="conectar">
                            <input type="hidden" name="destinatario_id" value="<?= (int) $persona['id'] ?>">
                            <button type="submit">Conectar</button>
                        </form>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
