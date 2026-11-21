<?php
session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'guia') {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../config/db.php';
$guiaId = (int) $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aviso_id'])) {
    $avisoId = filter_input(INPUT_POST, 'aviso_id', FILTER_VALIDATE_INT);
    if ($avisoId) {
        $stmt = $pdo->prepare('DELETE FROM avisos WHERE id = ? AND guia_id = ?');
        $stmt->execute([$avisoId, $guiaId]);
    }
}

$stmt = $pdo->prepare(
    'SELECT id, titulo, categoria, contenido, whatsapp, fecha
     FROM avisos WHERE guia_id = ? ORDER BY fecha DESC'
);
$stmt->execute([$guiaId]);
$avisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$esc = static fn($valor) => htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel guía | Aterriza</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
    <a href="index.php"><strong>Aterriza</strong></a>
    <div>
        <a href="avisos_guia.php">Publicar Aviso</a>
        <a href="logout.php">Salir</a>
    </div>
</nav>
<main>
    <h1>Hola, <?= $esc($_SESSION['nombre']) ?></h1>
    <h2>Mis avisos</h2>
    <?php if (!$avisos): ?>
        <p>No has publicado avisos.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($avisos as $aviso): ?>
                <section class="card">
                    <h2><?= $esc($aviso['titulo']) ?></h2>
                    <p><strong><?= $esc($aviso['categoria']) ?></strong></p>
                    <p><?= nl2br($esc($aviso['contenido'])) ?></p>
                    <form method="post">
                        <input type="hidden" name="aviso_id" value="<?= (int) $aviso['id'] ?>">
                        <button type="submit">Borrar aviso</button>
                    </form>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
