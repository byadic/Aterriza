<?php
session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'foraneo') {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../config/db.php';
$zona = isset($_GET['zona']) && is_string($_GET['zona']) ? trim($_GET['zona']) : '';
$precioFiltro = isset($_GET['precio']) && is_string($_GET['precio']) ? trim($_GET['precio']) : '';
$error = '';
$precioMaximo = null;
if ($precioFiltro !== '') {
    if (is_numeric($precioFiltro) && (float) $precioFiltro >= 0) {
        $precioMaximo = (float) $precioFiltro;
    } else {
        $error = 'Escribe un precio máximo válido.';
    }
}

$sql = 'SELECT titulo, zona, precio, descripcion, whatsapp FROM cuartos WHERE 1=1';
$params = [];
if ($zona !== '') {
    $sql .= ' AND zona LIKE ?';
    $params[] = '%' . $zona . '%';
}
if ($precioMaximo !== null) {
    $sql .= ' AND precio <= ?';
    $params[] = $precioMaximo;
}
$sql .= ' ORDER BY id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cuartos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$esc = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cuartos Disponibles | Aterriza</title>
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
    <h1>Cuartos Disponibles</h1>
    <form class="filters" method="get">
        <div>
            <label for="zona">Zona</label>
            <input id="zona" name="zona" value="<?= $esc($zona) ?>">
        </div>
        <div>
            <label for="precio">Precio máximo</label>
            <input id="precio" name="precio" type="number" min="0" step="0.01" value="<?= $esc($precioFiltro) ?>">
        </div>
        <button type="submit">Filtrar</button>
    </form>
    <?php if ($error): ?><p class="error"><?= $esc($error) ?></p><?php endif; ?>
    <?php if (!$cuartos): ?>
        <p>No se encontraron cuartos con esos filtros.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($cuartos as $cuarto): ?>
                <?php $whatsapp = preg_replace('/\D+/', '', $cuarto['whatsapp']); ?>
                <section class="card">
                    <h2><?= $esc($cuarto['titulo']) ?></h2>
                    <p><strong>Zona:</strong> <?= $esc($cuarto['zona']) ?></p>
                    <p><strong>Precio mensual:</strong> $<?= number_format((float) $cuarto['precio'], 2) ?></p>
                    <p><?= $esc($cuarto['descripcion']) ?></p>
                    <a class="button contact" href="https://wa.me/<?= $esc($whatsapp) ?>" target="_blank" rel="noopener">WhatsApp</a>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <p class="safety-note">No pagues adelantos sin ver el cuarto.</p>
</main>
</body>
</html>
