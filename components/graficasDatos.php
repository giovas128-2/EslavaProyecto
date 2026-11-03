<?php
require_once __DIR__.'/../includes/adminGuard.php';require_once __DIR__.'/../includes/analitica.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store, private');
if($_SERVER['REQUEST_METHOD']!=='GET'){http_response_code(405);exit;}
try{echo json_encode(datosGraficas($conexion,filtrosGraficas($_GET)),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);}
catch(Throwable $e){http_response_code(422);echo json_encode(['error'=>$e instanceof PDOException?'No se pudieron consultar pedidos y bitácora. Revisa la base de datos.':($e instanceof RuntimeException?$e->getMessage():'No se pudieron cargar las gráficas.')]);}
