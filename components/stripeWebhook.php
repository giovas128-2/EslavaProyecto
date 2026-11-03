<?php
// Public endpoint: authentication is the Stripe signature, not a browser session.
require_once __DIR__.'/../includes/comercio.php';date_default_timezone_set('America/Mexico_City');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit;}
$raw=file_get_contents('php://input');$secret=tiendaConfig()['stripe_webhook_secret'];
$parts=explode(',',$_SERVER['HTTP_STRIPE_SIGNATURE']??'');$timestamp=0;$signatures=[];
foreach($parts as $part){$v=explode('=',$part,2);if(count($v)!==2){continue;}if($v[0]==='t'){$timestamp=(int)$v[1];}if($v[0]==='v1'){$signatures[]=$v[1];}}
$expected=hash_hmac('sha256',$timestamp.'.'.$raw,$secret);$valid=false;
foreach($signatures as $sig){if(hash_equals($expected,$sig)){$valid=true;}}
if(!$secret || !$valid || abs(time()-$timestamp)>300){http_response_code(400);exit('Invalid signature');}
try{
 $event=json_decode($raw,true,512,JSON_THROW_ON_ERROR);
 if(in_array($event['type']??'',['checkout.session.completed','checkout.session.async_payment_succeeded','checkout.session.expired'],true)){
  $session=$event['data']['object'];
  // Ignore other stores' sessions sharing the Stripe account.
  if(isset($session['metadata']['pedido_id'],$session['metadata']['folio'])){
   $verified=stripeAPI('GET','checkout/sessions/'.rawurlencode($session['id']));aplicarSesionStripe($verified);
  }
 }
 if(in_array($event['type']??'',['payment_intent.succeeded','payment_intent.canceled','payment_intent.payment_failed','payment_intent.processing'],true)){
  $intent=$event['data']['object'];
  if(($intent['metadata']['guitarixta']??'')==='1'){
   $verified=stripeAPI('GET','payment_intents/'.rawurlencode($intent['id']));aplicarIntentoStripe($verified);
  }
 }
 http_response_code(200);echo 'OK';
}catch(Throwable $e){error_log('Webhook Guitarixta: unable to process verified event');http_response_code(500);echo 'Retry later';}
