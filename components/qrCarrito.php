<?php
require_once __DIR__.'/../includes/sesion.php';
require_once __DIR__.'/../includes/productoQr.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD']!=='POST') {http_response_code(405);header('Allow: POST');exit;}
if (!csrfValido()) {http_response_code(403);echo json_encode(['error'=>'Actualiza la página para generar el QR.']);exit;}
if (empty($_SESSION['usuario'])) {echo json_encode(['login_required'=>true]);exit;}
try {
    $u=usuarioCompra();
    echo json_encode(['urls'=>guardarQrCarrito(dbTienda(),$u,cargarCarrito())],JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    error_log('Guitarixta QR: '.$e->getCode());
    http_response_code(503);
    echo json_encode(['error'=>'No se pudo generar el QR. Intenta de nuevo más tarde.']);
}
