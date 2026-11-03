<?php
require_once __DIR__.'/../includes/barcode.php';
header('Content-Type: image/svg+xml');header('X-Content-Type-Options: nosniff');
$code=is_string($_GET['codigo']??null)?$_GET['codigo']:'';
echo codigoBarras($code);
