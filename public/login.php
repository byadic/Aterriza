<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header('Location: ' . ($_SESSION['rol'] === 'guia' ? 'panel_guia.php' : 'panel_foraneo.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/../config/db.php';
    $stmt = $pdo->prepare('SELECT id, nombre, `password`, rol FROM usuarios WHERE correo = ?');
    $stmt->execute([trim($_POST['correo'] ?? '')]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($_POST['password'] ?? '', $usuario['password'])) {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['rol'] = $usuario['rol'];
        header('Location: ' . ($usuario['rol'] === 'guia' ? 'panel_guia.php' : 'panel_foraneo.php'));
        exit;
    }
    $error = 'Correo o contraseña incorrectos.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | Aterriza</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
    <a href="index.php"><strong>Aterriza</strong></a>
    <div><a href="registro.php">Registrarse</a></div>
</nav>
<main>
    <section class="card">
        <h1>Iniciar Sesión</h1>
        <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="post">
            <label for="correo">Correo</label>
            <input id="correo" name="correo" type="email" required>

            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password" required>

            <p><button type="submit">Iniciar Sesión</button></p>
        </form>
    </section>
</main>
</body>
</html>
