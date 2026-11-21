<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../config/db.php';
$usuarioId = (int) $_SESSION['usuario_id'];
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'aceptar') {
    $conexionId = filter_input(INPUT_POST, 'conexion_id', FILTER_VALIDATE_INT);
    if ($conexionId) {
        $stmt = $pdo->prepare(
            "UPDATE conexiones SET estado = 'aceptada'
             WHERE id = ? AND destinatario_id = ? AND estado = 'pendiente'"
        );
        $stmt->execute([$conexionId, $usuarioId]);
        $mensaje = $stmt->rowCount() ? 'Solicitud aceptada.' : 'La solicitud ya no está disponible.';
    } else {
        $mensaje = 'Solicitud no válida.';
    }
}

$stmt = $pdo->prepare(
    "SELECT c.id, u.nombre FROM conexiones c
     JOIN usuarios u ON u.id = c.solicitante_id
     WHERE c.destinatario_id = ? AND c.estado = 'pendiente'
     ORDER BY c.id DESC"
);
$stmt->execute([$usuarioId]);
$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    "SELECT u.nombre, u.whatsapp FROM conexiones c
     JOIN usuarios u ON u.id = CASE
         WHEN c.solicitante_id = ? THEN c.destinatario_id
         ELSE c.solicitante_id
     END
     WHERE (c.solicitante_id = ? OR c.destinatario_id = ?)
       AND c.estado = 'aceptada'
     ORDER BY c.id DESC"
);
$stmt->execute([$usuarioId, $usuarioId, $usuarioId]);
$conexiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
$esc = static fn($valor) => htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi panel | Aterriza</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
    <a href="index.php"><strong>Aterriza</strong></a>
    <div><a href="personas.php">Personas</a><a href="cuartos.php">Cuartos</a><a href="logout.php">Salir</a></div>
</nav>
<main>
    <h1>Hola, <?= $esc($_SESSION['nombre'] ?? 'estudiante') ?></h1>
    <div class="grid">
        <a class="button" href="personas.php">Explorar Personas</a>
        <a class="button secondary" href="cuartos.php">Ver Cuartos</a>
    </div>

    <?php if ($mensaje): ?><p><?= $esc($mensaje) ?></p><?php endif; ?>

    <h2>Solicitudes recibidas</h2>
    <?php if (!$solicitudes): ?>
        <p>No tienes solicitudes pendientes.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($solicitudes as $solicitud): ?>
                <section class="card">
                    <strong><?= $esc($solicitud['nombre']) ?></strong>
                    <form method="post">
                        <input type="hidden" name="accion" value="aceptar">
                        <input type="hidden" name="conexion_id" value="<?= (int) $solicitud['id'] ?>">
                        <button type="submit">Aceptar</button>
                    </form>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2>Conexiones aceptadas</h2>
    <?php if (!$conexiones): ?>
        <p>Aún no tienes conexiones aceptadas.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($conexiones as $conexion): ?>
                <?php $whatsapp = preg_replace('/\D+/', '', $conexion['whatsapp']); ?>
                <section class="card">
                    <strong><?= $esc($conexion['nombre']) ?></strong>
                    <p><a class="button contact" href="https://wa.me/<?= $esc($whatsapp) ?>" target="_blank" rel="noopener">Hablar por WhatsApp</a></p>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
