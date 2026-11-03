<?php
require_once __DIR__.'/../includes/sesion.php';require_once __DIR__.'/../includes/comercio.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store, private');
try{
 if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit;}
 if(!csrfValido()){http_response_code(403);throw new RuntimeException('Sesión expirada. Actualiza la página.');}
 $p=pedidoVisible((int)($_POST['id']??0));
 if(!in_array($_POST['accion']??'',['preparar','estado'],true)){throw new RuntimeException('Acción inválida.');}
 if(($p['stripe_motor']??'')!=='intent'){throw new RuntimeException('Este pedido utiliza otro formulario de pago.');}
 if($p['estado']==='pendiente_tarjeta'){$s=prepararIntentoStripe($p);$p=pedidoVisible((int)$p['id']);}
 $out=['estado'=>$p['estado'],'total_centavos'=>(int)$p['total_centavos'],'prueba'=>$p['stripe_modo']==='test','url'=>'pedido.php?id='.$p['id']];
 if(($_POST['accion']??'')==='preparar' && $p['estado']==='pendiente_tarjeta' && in_array($s['status']??'',['requires_payment_method','requires_confirmation','requires_action'],true) && time()<=(int)$p['vencimiento_unix']){$out['client_secret']=$s['client_secret'];}
 echo json_encode($out,JSON_THROW_ON_ERROR);
}catch(Throwable $e){if(http_response_code()<400){http_response_code(422);}echo json_encode(['error'=>$e instanceof PDOException?'No se pudo consultar el pedido. Importa actualizar_iva_tarjeta.sql.':($e instanceof RuntimeException?$e->getMessage():'No se pudo consultar el pago.')]);}
