<?php
// GET: tus publicaciones. POST (multipart): tipo, texto y fotos[] (las fotos son opcionales si hay texto).
require_once __DIR__ . '/../config/db.php';
$u = requiere_sesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? 'foto';
    if (!in_array($tipo, ['foto', 'ayuda', 'plan', 'recomendacion', 'otro'], true)) $tipo = 'foto';
    $texto = mb_substr(trim($_POST['texto'] ?? ''), 0, 500);
    $f = $_FILES['fotos'] ?? null;
    $hayFotos = $f && !empty($f['name'][0]);

    $ins = $pdo->prepare('INSERT INTO publicaciones (usuario_id, tipo, ruta_imagen, descripcion) VALUES (?, ?, ?, ?)');

    if (!$hayFotos) {
        if ($tipo === 'foto' || $texto === '') responder(false, 'Escribe algo o selecciona una foto.', [], 422);
        $ins->execute([$u['id'], $tipo, null, $texto]);
        responder(true, 'Publicado.');
    }

    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $guardadas = 0; $problema = '';

    foreach ($f['name'] as $i => $n) {
        if (in_array($f['error'][$i], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $f['size'][$i] > 5 * 1024 * 1024) { $problema = 'Una foto es demasiado grande (máx. 2 MB por defecto en XAMPP).'; continue; }
        if ($f['error'][$i] !== UPLOAD_ERR_OK) { $problema = 'No se pudo subir una foto.'; continue; }
        $mime = finfo_file($finfo, $f['tmp_name'][$i]);
        if (!isset($tipos[$mime])) { $problema = 'Solo se permiten imágenes JPG, PNG, WEBP o GIF.'; continue; }
        $archivo = bin2hex(random_bytes(8)) . '.' . $tipos[$mime];
        if (!move_uploaded_file($f['tmp_name'][$i], "$dir/$archivo")) { $problema = 'No se pudo guardar una foto.'; continue; }
        $ins->execute([$u['id'], $tipo, "uploads/$archivo", $texto ?: null]);
        $guardadas++;
    }
    if ($guardadas === 0) responder(false, $problema ?: 'No se pudo guardar ninguna foto.', [], 422);
    responder(true, "$guardadas publicación(es) creada(s).");
}

$st = $pdo->prepare('SELECT id, tipo, ruta_imagen AS src, descripcion AS caption, fecha FROM publicaciones WHERE usuario_id = ? ORDER BY id DESC');
$st->execute([$u['id']]);
responder(true, '', ['posts' => $st->fetchAll()]);
