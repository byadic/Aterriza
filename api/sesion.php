<?php
// aterriza.html lo llama al cargar: si no hay sesión responde 401 y la página redirige al login
require_once __DIR__ . '/../config/db.php';
$u = requiere_sesion();
responder(true, '', ['usuario' => $u]);
