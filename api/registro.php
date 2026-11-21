<?php
// Recibe el JSON de AMBOS formularios y detecta el tipo por sus campos:
//   Foráneo:   nombre, universidad, carrera, correo, password
//   Residente: nombre, edad, sexo, origen, correo, password
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(false, 'Método no permitido.', [], 405);

$d        = leer_json();
$nombre   = trim($d['nombre'] ?? '');
$correo   = strtolower(trim($d['correo'] ?? ''));
$password = (string)($d['password'] ?? '');

if ($nombre === '' || $correo === '' || $password === '') responder(false, 'Completa todos los campos.', [], 422);
if (mb_strlen($nombre) > 100) responder(false, 'El nombre es demasiado largo.', [], 422);
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) responder(false, 'El correo no es válido.', [], 422);
if (strlen($password) < 8) responder(false, 'La contraseña debe tener al menos 8 caracteres.', [], 422);

$esForaneo   = isset($d['universidad']) || isset($d['carrera']);
$esResidente = isset($d['edad']) || isset($d['origen']);
if ($esForaneo === $esResidente) responder(false, 'Tipo de registro no válido.', [], 422);

$st = $pdo->prepare('SELECT 1 FROM usuarios WHERE correo = ?');
$st->execute([$correo]);
if ($st->fetchColumn()) responder(false, 'Ese correo ya está registrado.', [], 409);

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    if ($esForaneo) {
        $universidad = trim($d['universidad'] ?? '');
        $carrera     = trim($d['carrera'] ?? '');
        if ($universidad === '' || $carrera === '') responder(false, 'Completa todos los campos.', [], 422);
        if (mb_strlen($universidad) > 150 || mb_strlen($carrera) > 150) responder(false, 'Hay campos demasiado largos.', [], 422);
        $pdo->prepare("INSERT INTO usuarios (rol, nombre_completo, correo, contrasena, universidad, carrera)
                       VALUES ('foraneo', ?, ?, ?, ?, ?)")
            ->execute([$nombre, $correo, $hash, $universidad, $carrera]);
    } else {
        $edad   = filter_var($d['edad'] ?? null, FILTER_VALIDATE_INT);
        $sexo   = trim($d['sexo'] ?? '');
        $origen = trim($d['origen'] ?? '');
        if ($edad === false || $edad < 18 || $edad > 120) responder(false, 'Ingresa una edad válida (mínimo 18).', [], 422);
        if (!in_array($sexo, ['Femenino', 'Masculino', 'Otro', 'Prefiero no decirlo'], true)) responder(false, 'Selecciona una opción de sexo.', [], 422);
        if ($origen === '' || mb_strlen($origen) > 150) responder(false, 'Indica de dónde eres.', [], 422);
        $pdo->prepare("INSERT INTO usuarios (rol, nombre_completo, correo, contrasena, edad, sexo, procedencia)
                       VALUES ('residente', ?, ?, ?, ?, ?, ?)")
            ->execute([$nombre, $correo, $hash, $edad, $sexo, $origen]);
    }
} catch (PDOException $e) {
    if ($e->getCode() === '23000') responder(false, 'Ese correo ya está registrado.', [], 409);
    responder(false, 'No se pudo crear la cuenta. Intenta de nuevo.', [], 500);
}

responder(true, 'Cuenta creada. Ya puedes iniciar sesión.');
