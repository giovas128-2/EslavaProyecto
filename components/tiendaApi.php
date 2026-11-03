<?php
require_once __DIR__.'/../includes/sesion.php';require_once __DIR__.'/../includes/comercio.php';
header('Content-Type: application/json; charset=utf-8');
try{
 if($_SERVER['REQUEST_METHOD']==='GET'){echo json_encode(cargarCarrito(),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);exit;}
 if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit;}
 if(!csrfValido()){http_response_code(403);throw new RuntimeException('La sesión expiró. Actualiza la página.');}
 $accion=$_POST['accion']??'';
 if($accion==='comprar'){
  $p=crearPedido($_POST,(string)($_POST['token']??''));$url='pedido.php?id='.$p['id'];
  if($p['metodo']==='tarjeta'){
   try{$url=iniciarStripe($p);}catch(RuntimeException $e){$_SESSION['pago_aviso']=$e->getMessage();}
  }
  echo json_encode(['url'=>$url]);exit;
 }
 if(!in_array($accion,['agregar','cantidad','quitar'],true)){throw new RuntimeException('Acción inválida.');}
 $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(!$id){throw new RuntimeException('Producto inválido.');}
 if($accion==='quitar'){unset($_SESSION['carrito'][$id]);}
 else{
  $qty=filter_var($_POST['cantidad']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>99]]);if(!$qty){throw new RuntimeException('Selecciona de 1 a 99 piezas.');}
  $qty=$accion==='agregar'?(int)($_SESSION['carrito'][$id]??0)+$qty:$qty;
  $q=dbTienda()->prepare('SELECT stock,activo,precio FROM productos WHERE id=?');$q->execute([$id]);$p=$q->fetch();
  if(!$p || !$p['activo'] || centavos($p['precio'])<1000 || $qty>99 || $qty>(int)$p['stock']){throw new RuntimeException('No hay suficientes piezas disponibles.');}
  if(!isset($_SESSION['carrito'][$id]) && count($_SESSION['carrito']??[])>=40){throw new RuntimeException('Máximo 40 productos distintos.');}
  $_SESSION['carrito'][$id]=$qty;
 }
 echo json_encode(cargarCarrito(),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}catch(PDOException $e){error_log('Tienda DB: '.$e->getCode());http_response_code(503);echo json_encode(['error'=>'No se pudo consultar la tienda. Revisa MySQL y la migración actualizar_tienda.sql.']);}
catch(Throwable $e){if(http_response_code()<400){http_response_code(422);}echo json_encode(['error'=>$e instanceof RuntimeException?$e->getMessage():'No se pudo completar la solicitud.']);}
