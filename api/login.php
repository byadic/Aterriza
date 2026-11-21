<?php
// Recibe JSON: { correo, password }. Sirve para foráneos y residentes.
session_start();
require_once __DIR__ . '/../config/db.php';

const PANEL_URL = 'aterriza.html';   // página a la que entra al iniciar sesión

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(false, 'Método no permitido.', [], 405);

$d        = leer_json();
$correo   = strtolower(trim($d['correo'] ?? ''));
$password = (string)($d['password'] ?? '');
if ($correo === '' || $password === '') responder(false, 'Ingresa tu correo y contraseña.', [], 422);

$st = $pdo->prepare('SELECT id, nombre_completo, rol, contrasena FROM usuarios WHERE correo = ? LIMIT 1');
$st->execute([$correo]);
$u = $st->fetch();

if (!$u || !password_verify($password, $u['contrasena'])) {
    responder(false, 'Correo o contraseña incorrectos.', [], 401);
}

session_regenerate_id(true);
$_SESSION['usuario_id'] = $u['id'];
$_SESSION['nombre']     = $u['nombre_completo'];
$_SESSION['rol']        = $u['rol'];   // 'foraneo' o 'residente'

responder(true, 'Bienvenido, ' . $u['nombre_completo'] . '.', ['redirect' => PANEL_URL, 'rol' => $u['rol']]);
