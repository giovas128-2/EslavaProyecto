<?php
require_once __DIR__.'/conexion.php';
function dbTienda(): PDO { static $db; if (!$db) { $db=conectarBaseDatos(require __DIR__.'/config.php'); } return $db; }
function tiendaConfig(): array { static $c; if (!$c) {$c=require __DIR__.'/comercio_config.php';} return $c; }
function centavos($valor): int { $s=(string)$valor; if(!preg_match('/^([0-9]{1,8})(?:\.([0-9]{1,2}))?$/',$s,$m)){throw new RuntimeException('Precio inválido.');} return (int)$m[1]*100+(int)str_pad($m[2]??'',2,'0'); }
// Redondeo a centavos: IVA del 16% sobre el subtotal de productos.
function ivaProductos(int $subtotal): int {return intdiv($subtotal*16+50,100);}
function dinero(int $c): string {return '$'.number_format($c/100,2).' MXN';}
function codigoProducto(array $p): string {return $p['codigo'] ?: 'GX'.str_pad((string)$p['id'],8,'0',STR_PAD_LEFT);}
function usuarioCompra(): array {
 $u=$_SESSION['usuario']??null;
 if(!$u){throw new RuntimeException('Inicia sesión para continuar.');}
 $map=['registrarAdmins'=>'nombre','registroUsuarios'=>'nombre','empleados'=>'nombre','login'=>'correo'];
 if(!isset($map[$u['origen']])){throw new RuntimeException('Sesión inválida.');}
 $q=dbTienda()->prepare('SELECT id FROM `'.$u['origen'].'` WHERE id=?');$q->execute([$u['id']]);
 if(!$q->fetch()){unset($_SESSION['usuario']);throw new RuntimeException('Esta cuenta ya no existe. Inicia sesión nuevamente.');}
 return $u;
}
function cargarCarrito(): array {
 $items=[];$total=0;
 foreach(($_SESSION['carrito']??[]) as $id=>$cantidad){
  $q=dbTienda()->prepare('SELECT id,producto,precio,stock,codigo,activo FROM productos WHERE id=?');$q->execute([(int)$id]);$p=$q->fetch();
  if(!$p){unset($_SESSION['carrito'][$id]);continue;}
  $c=centavos($p['precio']);$items[]=['id'=>(int)$p['id'],'producto'=>$p['producto'],'codigo'=>codigoProducto($p),'cantidad'=>(int)$cantidad,'precio_centavos'=>$c,'stock'=>(int)$p['stock'],'disponible'=>(bool)$p['activo']];$total+=$c*(int)$cantidad;
 }
 return ['items'=>$items,'subtotal_centavos'=>$total,'cantidad'=>array_sum(array_column($items,'cantidad'))];
}
function pedidoVisible(int $id): array {
 $u=usuarioCompra();$q=dbTienda()->prepare('SELECT * FROM pedidos WHERE id=?');$q->execute([$id]);$p=$q->fetch();
 if(!$p || ($u['origen']!=='registrarAdmins' && ((int)$p['usuario_id']!==(int)$u['id'] || $p['usuario_origen']!==$u['origen']))){throw new RuntimeException('Pedido no encontrado.');}
 return $p;
}
function detallesPedido(int $id): array {$q=dbTienda()->prepare('SELECT * FROM pedido_detalles WHERE pedido_id=? ORDER BY producto_id');$q->execute([$id]);return $q->fetchAll();}
function stripeDisponible(): bool {
 $c=tiendaConfig();$key=$c['stripe_secret'];
 if(!$key || !preg_match('/^pk_'.(strpos($key,'sk_live_')===0?'live':'test').'_/',$c['stripe_public']??'') || !$c['stripe_webhook_secret'] || !function_exists('curl_init')){return false;}
 if(strpos($key,'sk_live_')===0){return $c['stripe_live_enabled'] && strpos($c['base_url'],'https://')===0;}
 return strpos($key,'sk_test_')===0;
}
function stripeAPI(string $method,string $path,array $params=[],string $key=''): array {
 $c=tiendaConfig();if(!$c['stripe_secret']){throw new RuntimeException('Pago con tarjeta aún no disponible.');}
 if(!function_exists('curl_init')){throw new RuntimeException('Habilita la extensión cURL en PHP.');}
 if($method==='GET' && $params){$path.='?'.http_build_query($params);}
 $ch=curl_init('https://api.stripe.com/v1/'.$path);
 $headers=['Authorization: Bearer '.$c['stripe_secret'],'Stripe-Version: 2024-06-20'];if($key){$headers[]='Idempotency-Key: '.$key;}
 curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>30,CURLOPT_HTTPHEADER=>$headers,CURLOPT_CUSTOMREQUEST=>$method]);
 if($method==='POST'){curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($params));}
 $raw=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
 if($raw===false || $status>=500){throw new RuntimeException('Stripe no respondió. Tu pedido está guardado; reintenta el pago desde Mis pedidos.');}
 $r=json_decode($raw,true);
 if($status<200 || $status>=300 || !is_array($r)){throw new RuntimeException('No se pudo iniciar o consultar el pago. Revisa la configuración de Stripe.');}
 return $r;
}
function aplicarSesionStripe(array $s): void {
 if(!isset($s['id'])){throw new RuntimeException('Respuesta de pago inválida.');}
 $db=dbTienda();$db->beginTransaction();
 try {
  $q=$db->prepare('SELECT * FROM pedidos WHERE stripe_session=? OR (id=? AND stripe_session IS NULL) FOR UPDATE');$q->execute([$s['id'],(int)($s['metadata']['pedido_id']??0)]);$p=$q->fetch();
  if(!$p || ($p['stripe_motor']??'')==='intent' || $p['metodo']!=='tarjeta' || (string)$p['id']!==($s['metadata']['pedido_id']??'') || $p['folio']!==($s['metadata']['folio']??'')){throw new RuntimeException('Pago sin pedido coincidente.');}
  if((int)($s['amount_total']??-1)!==(int)$p['total_centavos'] || ($s['currency']??'')!=='mxn' || (bool)($s['livemode']??false)!==($p['stripe_modo']==='live')){throw new RuntimeException('El importe o modo del pago no coincide.');}
  $db->prepare('UPDATE pedidos SET stripe_session=? WHERE id=?')->execute([$s['id'],$p['id']]);
  if($p['estado']==='pendiente_tarjeta'){
   if(($s['payment_status']??'')==='paid'){
    $db->prepare("UPDATE pedidos SET estado='pagado',pagado_en=?,admin_visto=0,stripe_url=NULL WHERE id=?")->execute([date('Y-m-d H:i:s'),$p['id']]);
   }elseif(($s['status']??'')==='expired'){
    restaurarStock($db,(int)$p['id']);$db->prepare("UPDATE pedidos SET estado='expirado',stripe_url=NULL,admin_visto=0 WHERE id=?")->execute([$p['id']]);
   }
  }
  $db->commit();
 }catch(Throwable $e){if($db->inTransaction()){$db->rollBack();}throw $e;}
}
function restaurarStock(PDO $db,int $id): void {
 $q=$db->prepare('SELECT producto_id,cantidad FROM pedido_detalles WHERE pedido_id=? ORDER BY producto_id');$q->execute([$id]);
 foreach($q->fetchAll() as $d){$db->prepare('UPDATE productos SET stock=stock+? WHERE id=?')->execute([$d['cantidad'],$d['producto_id']]);}
}
function iniciarStripe(array $p): string {
 if(!stripeDisponible()){throw new RuntimeException('Pago con tarjeta aún no disponible. El administrador debe configurar Stripe.');}
 if($p['estado']!=='pendiente_tarjeta'){return 'pedido.php?id='.$p['id'];}
 if(($p['stripe_motor']??'')==='intent'){return 'pagoTarjeta.php?id='.$p['id'];}
 if($p['stripe_modo']!==(strpos(tiendaConfig()['stripe_secret'],'sk_live_')===0?'live':'test')){throw new RuntimeException('Este pedido pertenece a otro modo de Stripe. Consulta al administrador.');}
 if($p['stripe_session']){
  $s=stripeAPI('GET','checkout/sessions/'.rawurlencode($p['stripe_session']));aplicarSesionStripe($s);
  if(($s['status']??'')==='open' && !empty($s['url'])){return $s['url'];}
  return 'pedido.php?id='.$p['id'];
 }
 if(time()>(int)$p['vencimiento_unix']-1800){
  $session=recuperarSesionStripe($p);
  if($session){aplicarSesionStripe($session);return $session['url']??('pedido.php?id='.$p['id']);}
  if(time()>(int)$p['vencimiento_unix']+600){
   // The configured expires_at is now in the past; no retry can create a new session.
   $db=dbTienda();$db->beginTransaction();
   try{$q=$db->prepare('SELECT estado,stripe_session FROM pedidos WHERE id=? FOR UPDATE');$q->execute([$p['id']]);$current=$q->fetch();
    if($current['estado']==='pendiente_tarjeta' && !$current['stripe_session']){restaurarStock($db,(int)$p['id']);$db->prepare("UPDATE pedidos SET estado='expirado',admin_visto=0 WHERE id=?")->execute([$p['id']]);}
    $db->commit();
   }catch(Throwable $e){if($db->inTransaction()){$db->rollBack();}throw $e;}
   return 'pedido.php?id='.$p['id'];
  }
  throw new RuntimeException('La sesión de pago no pudo crearse. Espera a su vencimiento para recuperar el stock; el administrador puede consultar el estado.');
 }
 // Same immutable payload + key make retries safe even after a timeout.
 $params=json_decode($p['stripe_payload'],true,512,JSON_THROW_ON_ERROR);
 $s=stripeAPI('POST','checkout/sessions',$params,'gx-checkout-'.$p['solicitud']);
 dbTienda()->prepare('UPDATE pedidos SET stripe_session=?,stripe_url=? WHERE id=? AND stripe_session IS NULL')->execute([$s['id'],$s['url']??null,$p['id']]);
 aplicarSesionStripe($s);
 return $s['url']??('pedido.php?id='.$p['id']);
}
function crearPedido(array $datos,string $token): array {
 $u=usuarioCompra();$db=dbTienda();
 if(!preg_match('/^[a-f0-9]{64}$/',$token)){throw new RuntimeException('Solicitud inválida. Actualiza la página.');}
 $q=$db->prepare('SELECT * FROM pedidos WHERE solicitud=?');$q->execute([$token]);$previo=$q->fetch();
 if($previo){if($previo['usuario_origen']!==$u['origen'] || (int)$previo['usuario_id']!==(int)$u['id']){throw new RuntimeException('Solicitud inválida.');}return $previo;}
 if(!hash_equals($_SESSION['compra_token']??'',$token)){throw new RuntimeException('Actualiza el resumen de compra.');}
 $metodo=$datos['metodo']??'';$entrega=$datos['entrega']??'';
 if(!in_array($metodo,['efectivo','tarjeta'],true) || !in_array($entrega,['tienda','domicilio'],true)){throw new RuntimeException('Selecciona pago y entrega.');}
 if($metodo==='tarjeta' && !stripeDisponible()){throw new RuntimeException('Pago con tarjeta todavía no disponible.');}
 $nombre=trim((string)($datos['nombre']??''));$correo=trim((string)($datos['correo']??''));$telefono=trim((string)($datos['telefono']??''));
 if($nombre==='' || strlen($nombre)>120 || !filter_var($correo,FILTER_VALIDATE_EMAIL) || strlen($correo)>254 || !preg_match('/^[0-9+() -]{7,25}$/',$telefono)){throw new RuntimeException('Revisa tu nombre, correo y teléfono.');}
 $direccion='';$refs='';$lat=null;$lon=null;
 if($entrega==='domicilio'){
  $direccion=trim((string)($datos['direccion']??''));$refs=trim((string)($datos['referencias']??''));
  $lat=filter_var($datos['latitud']??null,FILTER_VALIDATE_FLOAT);$lon=filter_var($datos['longitud']??null,FILTER_VALIDATE_FLOAT);
  if(strlen($direccion)<10 || strlen($direccion)>600 || strlen($refs)>300 || $lat===false || $lon===false || $lat<-90 || $lat>90 || $lon<-180 || $lon>180){throw new RuntimeException('Escribe el domicilio completo y marca su ubicación en el mapa.');}
 }
 $cart=$_SESSION['carrito']??[];if(!$cart || count($cart)>40){throw new RuntimeException('Tu carrito está vacío o excede 40 productos diferentes.');}ksort($cart,SORT_NUMERIC);
 $db->beginTransaction();
 try{
  $items=[];$subtotal=0;
  foreach($cart as $id=>$qty){
   $qty=filter_var($qty,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>99]]);if(!$qty){throw new RuntimeException('Cantidad inválida.');}
   $q=$db->prepare('SELECT id,producto,codigo,precio,stock,activo FROM productos WHERE id=? FOR UPDATE');$q->execute([(int)$id]);$pr=$q->fetch();
   if(!$pr || !$pr['activo'] || (int)$pr['stock']<$qty || centavos($pr['precio'])<1000){throw new RuntimeException('Un producto ya no está disponible en la cantidad solicitada. Revisa tu carrito.');}
   $price=centavos($pr['precio']);$subtotal+=$price*$qty;$items[]=['p'=>$pr,'qty'=>$qty,'price'=>$price];
  }
  $envio=$entrega==='domicilio'?3000:0;$iva=ivaProductos($subtotal);$total=$subtotal+$iva+$envio;
  if($total>99999999){throw new RuntimeException('El pedido supera el importe máximo permitido.');}
  $folio='GX-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(4)));$expiry=time()+2100;
  $db->prepare('INSERT INTO pedidos (folio,usuario_id,usuario_origen,nombre,correo,telefono,metodo,entrega,direccion,referencias,latitud,longitud,subtotal_centavos,envio_centavos,total_centavos,estado,creado_en,vencimiento_unix,solicitud,stripe_modo,iva_centavos,iva_porcentaje,stripe_motor) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$folio,$u['id'],$u['origen'],$nombre,$correo,$telefono,$metodo,$entrega,$direccion,$refs,$lat,$lon,$subtotal,$envio,$total,$metodo==='efectivo'?'pendiente_efectivo':'pendiente_tarjeta',date('Y-m-d H:i:s'),$metodo==='tarjeta'?$expiry:null,$token,$metodo==='tarjeta'?(strpos(tiendaConfig()['stripe_secret'],'sk_live_')===0?'live':'test'):null,$iva,16,$metodo==='tarjeta'?'intent':null]);
  $id=(int)$db->lastInsertId();$stripeItems=[];
  foreach($items as $it){$pr=$it['p'];
   $db->prepare('UPDATE productos SET stock=stock-? WHERE id=?')->execute([$it['qty'],$pr['id']]);
   $db->prepare('INSERT INTO pedido_detalles(pedido_id,producto_id,producto,codigo,cantidad,precio_centavos) VALUES(?,?,?,?,?,?)')->execute([$id,$pr['id'],$pr['producto'],codigoProducto($pr),$it['qty'],$it['price']]);
   $stripeItems[]=['price_data'=>['currency'=>'mxn','unit_amount'=>$it['price'],'product_data'=>['name'=>$pr['producto']]],'quantity'=>$it['qty']];
  }
  $db->commit();$_SESSION['carrito']=[];$_SESSION['compra_token']=bin2hex(random_bytes(32));
  $q=$db->prepare('SELECT * FROM pedidos WHERE id=?');$q->execute([$id]);return $q->fetch();
 }catch(Throwable $e){if($db->inTransaction()){$db->rollBack();}throw $e;}
}
function cambiarPedidoEfectivo(int $id,string $accion): void {
 $db=dbTienda();$db->beginTransaction();
 try{
  $q=$db->prepare('SELECT * FROM pedidos WHERE id=? FOR UPDATE');$q->execute([$id]);$p=$q->fetch();if(!$p){throw new RuntimeException('Pedido inexistente.');}
  if($accion==='cobrar' && $p['metodo']==='efectivo' && $p['estado']==='pendiente_efectivo'){$db->prepare("UPDATE pedidos SET estado='pagado',pagado_en=?,admin_visto=1 WHERE id=?")->execute([date('Y-m-d H:i:s'),$id]);}
  elseif($accion==='cancelar' && $p['metodo']==='efectivo' && $p['estado']==='pendiente_efectivo'){restaurarStock($db,$id);$db->prepare("UPDATE pedidos SET estado='cancelado',admin_visto=1 WHERE id=?")->execute([$id]);}
  elseif($accion==='entregar' && $p['estado']==='pagado'){$db->prepare("UPDATE pedidos SET estado='entregado',admin_visto=1 WHERE id=?")->execute([$id]);}
  elseif($accion==='visto'){$db->prepare('UPDATE pedidos SET admin_visto=1 WHERE id=?')->execute([$id]);}
  else{throw new RuntimeException('Este cambio no es válido para el estado actual del pedido.');}
  $db->commit();
 }catch(Throwable $e){if($db->inTransaction()){$db->rollBack();}throw $e;}
}

function recuperarSesionStripe(array $p): ?array {
 $params=['limit'=>100,'created'=>['gte'=>(int)$p['vencimiento_unix']-2400,'lte'=>(int)$p['vencimiento_unix']]];
 do{
  $r=stripeAPI('GET','checkout/sessions',$params);
  foreach(($r['data']??[]) as $s){if(($s['metadata']['folio']??'')===$p['folio'] && (string)($s['metadata']['pedido_id']??'')===(string)$p['id']){return $s;}}
  if(!empty($r['has_more'])){$last=end($r['data']);$params['starting_after']=$last['id'];}
 }while(!empty($r['has_more']));
 return null;
}

require_once __DIR__.'/pagoIntento.php';
