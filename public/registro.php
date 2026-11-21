<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header('Location: ' . ($_SESSION['rol'] === 'guia' ? 'panel_guia.php' : 'panel_foraneo.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/../config/db.php';

    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? '';
    $carrera = trim($_POST['carrera'] ?? '');
    $ciudad = trim($_POST['ciudad_origen'] ?? '');
    $ciudadDestino = trim($_POST['ciudad_destino'] ?? '');
    $intereses = trim($_POST['intereses'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');

    if (!$nombre || !filter_var($correo, FILTER_VALIDATE_EMAIL) || !$password ||
        !in_array($rol, ['foraneo', 'guia'], true) || !$carrera || !$ciudad || !$ciudadDestino ||
        !$intereses || !$whatsapp) {
        $error = 'Completa todos los campos correctamente.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO usuarios (nombre, correo, `password`, rol, carrera, ciudad_origen, ciudad_destino, intereses, whatsapp)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        try {
            $stmt->execute([
                $nombre, $correo, password_hash($password, PASSWORD_DEFAULT),
                $rol, $carrera, $ciudad, $ciudadDestino, $intereses, $whatsapp
            ]);
            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'Ese correo ya está registrado.';
            } else {
                throw $e;
            }
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrarse | Aterriza</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
    <a href="index.php"><strong>Aterriza</strong></a>
    <div><a href="login.php">Iniciar Sesión</a></div>
</nav>
<main>
    <section class="card">
        <h1>Crear cuenta</h1>
        <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="post">
            <label for="nombre">Nombre</label>
            <input id="nombre" name="nombre" maxlength="100" required>

            <label for="correo">Correo</label>
            <input id="correo" name="correo" type="email" maxlength="150" required>

            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password" required>

            <label for="rol">Tipo de usuario</label>
            <select id="rol" name="rol" required>
                <option value="foraneo">Foráneo</option>
                <option value="guia">Guía</option>
            </select>

            <label for="carrera">Carrera</label>
            <input id="carrera" name="carrera" maxlength="100" required>

            <label for="ciudad_origen">Ciudad de origen</label>
            <input id="ciudad_origen" name="ciudad_origen" maxlength="100" required>

            <label for="ciudad_destino">Ciudad de destino</label>
            <input id="ciudad_destino" name="ciudad_destino" maxlength="100" required>

            <label for="intereses">Intereses</label>
            <textarea id="intereses" name="intereses" required></textarea>

            <label for="whatsapp">WhatsApp</label>
            <input id="whatsapp" name="whatsapp" maxlength="20" required>

            <p><button type="submit">Registrarse</button></p>
        </form>
    </section>
</main>
</body>
</html>
