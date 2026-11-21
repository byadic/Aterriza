<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../config/db.php';
$usuarioId = (int) $_SESSION['usuario_id'];
$rol = isset($_GET['rol']) && is_string($_GET['rol']) ? $_GET['rol'] : '';
$ciudad = isset($_GET['ciudad_origen']) && is_string($_GET['ciudad_origen'])
    ? trim($_GET['ciudad_origen'])
    : '';
$rolesValidos = ['', 'foraneo', 'guia'];
if (!in_array($rol, $rolesValidos, true)) {
    $rol = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'conectar') {
    $destinatarioId = filter_input(INPUT_POST, 'destinatario_id', FILTER_VALIDATE_INT);
    if ($destinatarioId && $destinatarioId !== $usuarioId) {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE id = ?');
        $stmt->execute([$destinatarioId]);
        if ($stmt->fetchColumn()) {
            $stmt = $pdo->prepare(
                'SELECT id FROM conexiones
                 WHERE (solicitante_id = ? AND destinatario_id = ?)
                    OR (solicitante_id = ? AND destinatario_id = ?)
                 LIMIT 1'
            );
            $stmt->execute([$usuarioId, $destinatarioId, $destinatarioId, $usuarioId]);
            if (!$stmt->fetchColumn()) {
                $stmt = $pdo->prepare(
                    "INSERT INTO conexiones (solicitante_id, destinatario_id, estado)
                     VALUES (?, ?, 'pendiente')"
                );
                $stmt->execute([$usuarioId, $destinatarioId]);
            }
        }
    }
    header('Location: personas.php?' . http_build_query(['rol' => $rol, 'ciudad_origen' => $ciudad]));
    exit;
}

$sql = 'SELECT id, nombre, rol, ciudad_origen, carrera, intereses, whatsapp FROM usuarios WHERE id <> ?';
$params = [$usuarioId];
if ($rol !== '') {
    $sql .= ' AND rol = ?';
    $params[] = $rol;
}
if ($ciudad !== '') {
    $sql .= ' AND ciudad_origen LIKE ?';
    $params[] = '%' . $ciudad . '%';
}
$sql .= ' ORDER BY nombre';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$personas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    'SELECT solicitante_id, destinatario_id, estado FROM conexiones
     WHERE solicitante_id = ? OR destinatario_id = ?'
);
$stmt->execute([$usuarioId, $usuarioId]);
$estados = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $conexion) {
    $otroId = (int) ($conexion['solicitante_id'] == $usuarioId
        ? $conexion['destinatario_id']
        : $conexion['solicitante_id']);
    if (!isset($estados[$otroId]) || $conexion['estado'] === 'aceptada') {
        $estados[$otroId] = [
            'estado' => $conexion['estado'],
            'enviada' => (int) $conexion['solicitante_id'] === $usuarioId
        ];
    }
}
$esc = static fn($valor) => htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Explorar personas | Aterriza</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
    <a href="index.php"><strong>Aterriza</strong></a>
    <div><a href="panel.php">Mi panel</a><a href="cuartos.php">Cuartos</a><a href="logout.php">Salir</a></div>
</nav>
<main class="page-wide">
    <h1>Explorar Personas</h1>
    <form class="filters" method="get">
        <div>
            <label for="rol">Rol</label>
            <select id="rol" name="rol">
                <option value="" <?= $rol === '' ? 'selected' : '' ?>>Todos</option>
                <option value="foraneo" <?= $rol === 'foraneo' ? 'selected' : '' ?>>Foráneo</option>
                <option value="guia" <?= $rol === 'guia' ? 'selected' : '' ?>>Guía</option>
            </select>
        </div>
        <div>
            <label for="ciudad_origen">Ciudad de origen</label>
            <input id="ciudad_origen" name="ciudad_origen" value="<?= $esc($ciudad) ?>">
        </div>
        <button type="submit">Filtrar</button>
    </form>

    <?php if (!$personas): ?>
        <p>No se encontraron personas.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($personas as $persona): ?>
                <section class="card">
                    <h2><?= $esc($persona['nombre']) ?></h2>
                    <p><?= $persona['rol'] === 'guia' ? 'Guía' : 'Foráneo' ?> · <?= $esc($persona['ciudad_origen']) ?></p>
                    <p><strong>Carrera:</strong> <?= $esc($persona['carrera']) ?></p>
                    <p><strong>Intereses:</strong> <?= $esc($persona['intereses']) ?></p>
                    <?php $estado = $estados[(int) $persona['id']] ?? null; ?>
                    <?php if ($estado && $estado['estado'] === 'aceptada'): ?>
                        <?php $whatsapp = preg_replace('/\D+/', '', $persona['whatsapp']); ?>
                        <a class="button contact" href="https://wa.me/<?= $esc($whatsapp) ?>" target="_blank" rel="noopener">Hablar por WhatsApp</a>
                    <?php elseif ($estado): ?>
                        <p><?= $estado['enviada'] ? 'Solicitud enviada' : 'Solicitud recibida' ?></p>
                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="accion" value="conectar">
                            <input type="hidden" name="destinatario_id" value="<?= (int) $persona['id'] ?>">
                            <button type="submit">Quiero conectar</button>
                        </form>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
