<?php
// Stripe hosts every sensitive card field. This application never receives PAN or CVC.
function validarIntentoStripe(array $s,array $p): void {
 if(!preg_match('/^pi_[a-zA-Z0-9]+$/',(string)($s['id']??'')) || ($p['stripe_motor']??'')!=='intent' || $p['metodo']!=='tarjeta'
  || (string)$p['id']!==($s['metadata']['pedido_id']??'') || $p['folio']!==($s['metadata']['folio']??'')
  || ($s['metadata']['guitarixta']??'')!=='1' || (int)($s['amount']??-1)!==(int)$p['total_centavos']
  || ($s['currency']??'')!=='mxn' || (bool)($s['livemode']??false)!==($p['stripe_modo']==='live')
  || (!empty($p['stripe_intent']) && $p['stripe_intent']!==$s['id'])){throw new RuntimeException('El pago no coincide con este pedido.');}
}
function aplicarIntentoStripe(array $s): void {
 $db=dbTienda();$db->beginTransaction();
 try{
  $q=$db->prepare('SELECT * FROM pedidos WHERE id=? FOR UPDATE');$q->execute([(int)($s['metadata']['pedido_id']??0)]);$p=$q->fetch();
  if(!$p){throw new RuntimeException('Pedido no encontrado.');}validarIntentoStripe($s,$p);
  if(($s['status']??'')==='succeeded' && (int)($s['amount_received']??-1)!==(int)$p['total_centavos']){throw new RuntimeException('El importe recibido no coincide.');}
  $db->prepare('UPDATE pedidos SET stripe_intent=? WHERE id=?')->execute([$s['id'],$p['id']]);
  if($p['estado']==='pendiente_tarjeta'){
   if(($s['status']??'')==='succeeded'){$db->prepare("UPDATE pedidos SET estado='pagado',pagado_en=?,admin_visto=0 WHERE id=?")->execute([date('Y-m-d H:i:s'),$p['id']]);}
   elseif(($s['status']??'')==='canceled'){restaurarStock($db,(int)$p['id']);$db->prepare("UPDATE pedidos SET estado='expirado',admin_visto=0 WHERE id=?")->execute([$p['id']]);}
  }
  $db->commit();
 }catch(Throwable $e){if($db->inTransaction()){$db->rollBack();}throw $e;}
}
function recuperarIntentoStripe(array $p): ?array {
 $params=['limit'=>100,'created'=>['gte'=>(int)$p['vencimiento_unix']-2400]];
 do{$r=stripeAPI('GET','payment_intents',$params);
  foreach(($r['data']??[]) as $s){if(($s['metadata']['guitarixta']??'')==='1' && ($s['metadata']['folio']??'')===$p['folio'] && (string)($s['metadata']['pedido_id']??'')===(string)$p['id']){return $s;}}
  if(!empty($r['has_more'])){$last=end($r['data']);$params['starting_after']=$last['id'];}
 }while(!empty($r['has_more']));return null;
}
function prepararIntentoStripe(array $pedido): array {
 if(!stripeDisponible()){throw new RuntimeException('Falta configurar las claves de Stripe y su webhook.');}
 // Serializes remote creation for this order, including simultaneous tabs and cron.
 // Retry uses the same immutable body and idempotency key. Never recreate after its retention window.
 $db=dbTienda();$db->beginTransaction();
 try{
  $q=$db->prepare('SELECT * FROM pedidos WHERE id=? FOR UPDATE');$q->execute([$pedido['id']]);$p=$q->fetch();
  if(!$p || ($p['stripe_motor']??'')!=='intent' || $p['metodo']!=='tarjeta'){throw new RuntimeException('Pedido de tarjeta inválido.');}
  if($p['stripe_modo']!==(strpos(tiendaConfig()['stripe_secret'],'sk_live_')===0?'live':'test')){throw new RuntimeException('Las claves no corresponden al modo del pedido.');}
  if($p['stripe_intent']){$s=stripeAPI('GET','payment_intents/'.rawurlencode($p['stripe_intent']));}
  else{
   if($p['estado']!=='pendiente_tarjeta'){throw new RuntimeException('Este pedido ya está cerrado.');}
   if(time()>(int)$p['vencimiento_unix']+64800){
    $s=recuperarIntentoStripe($p);
    if(!$s){throw new RuntimeException('No se pudo recuperar el intento de pago. El administrador debe conciliarlo en Stripe antes de liberar la reserva.');}
   }else{
    $s=stripeAPI('POST','payment_intents',['amount'=>(int)$p['total_centavos'],'currency'=>'mxn','payment_method_types'=>['card'],'capture_method'=>'automatic','description'=>'Guitarixta '.$p['folio'],'metadata'=>['pedido_id'=>(string)$p['id'],'folio'=>$p['folio'],'guitarixta'=>'1']],'gx-intent-'.$p['solicitud']);
   }
  }
  validarIntentoStripe($s,$p);
  $db->prepare('UPDATE pedidos SET stripe_intent=? WHERE id=?')->execute([$s['id'],$p['id']]);$db->commit();
 }catch(Throwable $e){if($db->inTransaction()){$db->rollBack();}throw $e;}
 // Stock is released only after Stripe confirms cancellation. A concurrent success wins at Stripe.
 if($p['estado']==='pendiente_tarjeta' && time()>(int)$p['vencimiento_unix'] && in_array($s['status']??'',['requires_payment_method','requires_confirmation','requires_action'],true)){
  try{$s=stripeAPI('POST','payment_intents/'.rawurlencode($s['id']).'/cancel',[],'gx-expire-'.$p['solicitud']);}
  catch(Throwable $e){$s=stripeAPI('GET','payment_intents/'.rawurlencode($s['id']));}
 }
 aplicarIntentoStripe($s);return $s;
}
function sincronizarPagoStripe(array $p): void {
 if(($p['stripe_motor']??'')==='intent'){prepararIntentoStripe($p);}
 elseif($p['stripe_session']){aplicarSesionStripe(stripeAPI('GET','checkout/sessions/'.rawurlencode($p['stripe_session'])));}
 else{iniciarStripe($p);}
}
