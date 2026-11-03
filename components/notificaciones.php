<?php
require_once __DIR__.'/../includes/adminGuard.php';header('Content-Type: application/json; charset=utf-8');
try{$n=(int)$conexion->query('SELECT COUNT(*) FROM pedidos WHERE admin_visto=0')->fetchColumn();echo json_encode(['cantidad'=>$n]);}
catch(PDOException $e){http_response_code(503);echo json_encode(['error'=>'Pedidos no disponibles']);}
