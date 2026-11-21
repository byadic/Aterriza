<?php
session_start();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aterriza</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
    <a href="index.php"><strong>Aterriza</strong></a>
    <div>
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <a href="panel.php">Mi panel</a>
            <a href="logout.php">Salir</a>
        <?php else: ?>
            <a href="login.php">Iniciar Sesión</a>
        <?php endif; ?>
    </div>
</nav>
<main class="hero">
    <section class="card">
        <h1>Aterriza</h1>
        <p>Llegar a una ciudad nueva no debería significar llegar solo</p>
        <div class="actions">
            <a class="button" href="registro.php">Registrarse</a>
            <a class="button secondary" href="login.php">Iniciar Sesión</a>
        </div>
    </section>
</main>
</body>
</html>
