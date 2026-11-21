<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../config/db.php';
$zona = isset($_GET['zona']) && is_string($_GET['zona']) ? trim($_GET['zona']) : '';
$precioMax = isset($_GET['precio']) && is_string($_GET['precio']) ? trim($_GET['precio']) : '';
$sql = 'SELECT titulo, zona, precio, descripcion, whatsapp FROM cuartos WHERE 1=1';
$params = [];

if ($zona !== '') {
    $sql .= ' AND zona LIKE ?';
    $params[] = '%' . $zona . '%';
}
if ($precioMax !== '' && is_numeric($precioMax) && (float) $precioMax >= 0) {
    $sql .= ' AND precio <= ?';
    $params[] = (float) $precioMax;
}
$sql .= ' ORDER BY id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cuartos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$esc = static fn($valor) => htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cuartos | Aterriza</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
    <a href="index.php"><strong>Aterriza</strong></a>
    <div><a href="panel.php">Mi panel</a><a href="personas.php">Personas</a><a href="logout.php">Salir</a></div>
</nav>
<main class="page-wide room-list">
    <div class="page-heading">
        <h1>Cuartos disponibles</h1>
        <a class="button" href="publicar.php">Publicar Cuarto</a>
    </div>
    <form class="filters" method="get">
        <div>
            <label for="zona">Zona</label>
            <input id="zona" name="zona" value="<?= $esc($zona) ?>">
        </div>
        <div>
            <label for="precio">Precio máximo mensual</label>
            <input id="precio" name="precio" type="number" min="0" step="0.01" value="<?= $esc($precioMax) ?>">
        </div>
        <button type="submit">Filtrar</button>
    </form>

    <?php if (!$cuartos): ?>
        <p>No se encontraron cuartos.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($cuartos as $cuarto): ?>
                <?php
                $whatsapp = preg_replace('/\D+/', '', $cuarto['whatsapp']);
                $mensaje = rawurlencode('Hola, vi tu cuarto en Aterriza');
                ?>
                <section class="card">
                    <h2><?= $esc($cuarto['titulo']) ?></h2>
                    <p><strong>Zona:</strong> <?= $esc($cuarto['zona']) ?></p>
                    <p><strong>Precio mensual:</strong> $<?= number_format((float) $cuarto['precio'], 2) ?></p>
                    <p class="description"><?= $esc($cuarto['descripcion']) ?></p>
                    <a class="button contact" href="https://wa.me/<?= $esc($whatsapp) ?>?text=<?= $mensaje ?>" target="_blank" rel="noopener">Contactar por WhatsApp</a>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<aside class="safety-note">Consejo de seguridad: Nunca envíes dinero por adelantado sin visitar el cuarto primero.</aside>
</body>
</html>
