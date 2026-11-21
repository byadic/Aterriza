<?php
session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'foraneo') {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../config/db.php';
$usuarioId = (int) $_SESSION['usuario_id'];
$stmt = $pdo->prepare(
    'SELECT u.nombre, u.whatsapp
     FROM conexiones c
     JOIN usuarios u ON u.id = CASE
         WHEN c.solicitante_id = ? THEN c.destinatario_id
         ELSE c.solicitante_id
     END
     WHERE (c.solicitante_id = ? OR c.destinatario_id = ?)
       AND c.estado = \'aceptada\'
     ORDER BY c.id DESC'
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
    <title>Panel foráneo | Aterriza</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
    <a href="index.php"><strong>Aterriza</strong></a>
    <div>
        <a href="explorar_foraneo.php">Personas y Guías</a>
        <a href="cuartos_foraneo.php">Cuartos Disponibles</a>
        <a href="logout.php">Salir</a>
    </div>
</nav>
<main>
    <h1>Hola, <?= $esc($_SESSION['nombre']) ?></h1>
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
