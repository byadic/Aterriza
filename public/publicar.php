<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../config/db.php';
$error = '';
$datos = ['titulo' => '', 'zona' => '', 'precio' => '', 'descripcion' => '', 'whatsapp' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($datos as $campo => $_) {
        $datos[$campo] = isset($_POST[$campo]) && is_string($_POST[$campo])
            ? trim($_POST[$campo])
            : '';
    }

    if (!$datos['titulo'] || !$datos['zona'] || !$datos['descripcion'] ||
        !$datos['whatsapp'] || !is_numeric($datos['precio']) || (float) $datos['precio'] < 0 ||
        strlen($datos['titulo']) > 150 || strlen($datos['zona']) > 100 || strlen($datos['whatsapp']) > 20) {
        $error = 'Completa todos los campos correctamente.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO cuartos (usuario_id, titulo, zona, precio, descripcion, whatsapp)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            (int) $_SESSION['usuario_id'],
            $datos['titulo'],
            $datos['zona'],
            (float) $datos['precio'],
            $datos['descripcion'],
            $datos['whatsapp']
        ]);
        header('Location: cuartos.php');
        exit;
    }
}
$esc = static fn($valor) => htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Publicar cuarto | Aterriza</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
    <a href="index.php"><strong>Aterriza</strong></a>
    <div><a href="panel.php">Mi panel</a><a href="cuartos.php">Cuartos</a><a href="logout.php">Salir</a></div>
</nav>
<main>
    <section class="card">
        <h1>Publicar Cuarto</h1>
        <?php if ($error): ?><p class="error"><?= $esc($error) ?></p><?php endif; ?>
        <form method="post">
            <label for="titulo">Título</label>
            <input id="titulo" name="titulo" maxlength="150" value="<?= $esc($datos['titulo']) ?>" required>

            <label for="zona">Zona</label>
            <input id="zona" name="zona" maxlength="100" value="<?= $esc($datos['zona']) ?>" required>

            <label for="precio">Precio mensual</label>
            <input id="precio" name="precio" type="number" min="0" step="0.01" value="<?= $esc($datos['precio']) ?>" required>

            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" required><?= $esc($datos['descripcion']) ?></textarea>

            <label for="whatsapp">WhatsApp</label>
            <input id="whatsapp" name="whatsapp" maxlength="20" value="<?= $esc($datos['whatsapp']) ?>" required>

            <p><button type="submit">Publicar Cuarto</button></p>
        </form>
    </section>
</main>
</body>
</html>
