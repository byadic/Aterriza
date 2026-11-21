<?php
session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'guia') {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../config/db.php';
$guiaId = (int) $_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT whatsapp FROM usuarios WHERE id = ? AND rol = 'guia'");
$stmt->execute([$guiaId]);
$whatsappGuia = $stmt->fetchColumn();
if ($whatsappGuia === false) {
    http_response_code(403);
    exit('No se encontró el perfil de guía.');
}

$categorias = ['Transporte', 'Comida económica', 'Trámites', 'Zonas/Consejos', 'Otro'];
$titulo = $categoria = $contenido = '';
$whatsapp = (string) $whatsappGuia;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = isset($_POST['titulo']) && is_string($_POST['titulo']) ? trim($_POST['titulo']) : '';
    $categoria = isset($_POST['categoria']) && is_string($_POST['categoria']) ? $_POST['categoria'] : '';
    $contenido = isset($_POST['contenido']) && is_string($_POST['contenido']) ? trim($_POST['contenido']) : '';
    $whatsapp = isset($_POST['whatsapp']) && is_string($_POST['whatsapp'])
        ? trim($_POST['whatsapp'])
        : '';
    if ($whatsapp === '') {
        $whatsapp = (string) $whatsappGuia;
    }

    if ($titulo === '' || strlen($titulo) > 150 || !in_array($categoria, $categorias, true) ||
        $contenido === '' || strlen($whatsapp) > 20) {
        $error = 'Completa correctamente el título, la categoría y el contenido.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO avisos (guia_id, titulo, categoria, contenido, whatsapp)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$guiaId, $titulo, $categoria, $contenido, $whatsapp]);
        header('Location: avisos_guia.php');
        exit;
    }
}

$stmt = $pdo->query(
    "SELECT a.titulo, a.categoria, a.contenido, a.whatsapp, a.fecha, u.nombre AS guia
     FROM avisos a
     JOIN usuarios u ON u.id = a.guia_id
     WHERE u.rol = 'guia'
     ORDER BY a.fecha DESC, a.id DESC"
);
$avisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$esc = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Avisos de guías | Aterriza</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
    <a href="panel_guia.php"><strong>Aterriza</strong></a>
    <div><a href="panel_guia.php">Mi Panel</a><a href="logout.php">Salir</a></div>
</nav>
<main class="page-wide">
    <section class="card">
        <h1>Publicar un aviso</h1>
        <?php if ($error): ?><p class="error"><?= $esc($error) ?></p><?php endif; ?>
        <form method="post">
            <label for="titulo">Título</label>
            <input id="titulo" name="titulo" maxlength="150" value="<?= $esc($titulo) ?>" required>

            <label for="categoria">Categoría</label>
            <select id="categoria" name="categoria" required>
                <option value="">Selecciona una categoría</option>
                <?php foreach ($categorias as $opcion): ?>
                    <option value="<?= $esc($opcion) ?>" <?= $categoria === $opcion ? 'selected' : '' ?>><?= $esc($opcion) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="contenido">Contenido</label>
            <textarea id="contenido" name="contenido" required><?= $esc($contenido) ?></textarea>

            <label for="whatsapp">WhatsApp (opcional)</label>
            <input id="whatsapp" name="whatsapp" maxlength="20" value="<?= $esc($whatsapp) ?>">

            <p><button type="submit">Publicar aviso</button></p>
        </form>
    </section>

    <h2>Muro de avisos</h2>
    <?php if (!$avisos): ?>
        <p>Aún no hay avisos publicados.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($avisos as $aviso): ?>
                <?php $numero = preg_replace('/\D+/', '', $aviso['whatsapp']); ?>
                <article class="card">
                    <h2><?= $esc($aviso['titulo']) ?></h2>
                    <p><strong><?= $esc($aviso['categoria']) ?></strong></p>
                    <p><?= nl2br($esc($aviso['contenido'])) ?></p>
                    <p>Publicado por <?= $esc($aviso['guia']) ?> · <?= $esc($aviso['fecha']) ?></p>
                    <?php if ($numero !== ''): ?>
                        <a class="button contact" href="https://wa.me/<?= $esc($numero) ?>" target="_blank" rel="noopener">WhatsApp</a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
