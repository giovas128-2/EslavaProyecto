<?php
require_once __DIR__ . '/../includes/sesion.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValido()) {
    header('Location: ../page/index.php');
    exit;
}
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/bitacora.php';
if (isset($_SESSION['usuario'])) {
    try {
        $conexion = conectarBaseDatos(require __DIR__ . '/../includes/config.php');
        registrarEvento($conexion, $_SESSION['usuario']['correo'] ?? $_SESSION['usuario']['nombre'], 'salida', 'exitoso', $_SESSION['usuario']);
    } catch (PDOException $e) {
        error_log('No se pudo registrar el cierre de sesión.');
        $_SESSION['error'] = 'La sesión se cerró, pero no se pudo guardar la salida en la bitácora.';
    }
}
$aviso = $_SESSION['error'] ?? null;
$_SESSION = [];
$params = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires' => time() - 42000,
    'path' => $params['path'],
    'domain' => $params['domain'],
    'secure' => $params['secure'],
    'httponly' => $params['httponly'],
    'samesite' => 'Lax',
]);
session_destroy();
header('Location: ../page/login.php' . ($aviso ? '?salida_sin_registro=1' : ''), true, 303);
exit;
