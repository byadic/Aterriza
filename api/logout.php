<?php
session_start();
$_SESSION = [];
session_destroy();
header('Location: ../index_modificado.html');   // ajusta a tu página de inicio
exit;
