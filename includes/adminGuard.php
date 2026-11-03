<?php
require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/conexion.php';
if (($_SESSION['usuario']['origen'] ?? '') !== 'registrarAdmins') {
    header('Location: ../page/login.php'); exit;
}
try {
    $conexion = conectarBaseDatos(require __DIR__ . '/config.php');
    $q = $conexion->prepare('SELECT id FROM registrarAdmins WHERE id = ?');
    $q->execute([$_SESSION['usuario']['id']]);
    if (!$q->fetch()) { unset($_SESSION['usuario']); header('Location: ../page/login.php'); exit; }
} catch (PDOException $e) {
    http_response_code(503); exit('No se pudo conectar. Revisa includes/config.php y que MySQL esté iniciado.');
}
