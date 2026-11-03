<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/../includes/comercio.php';date_default_timezone_set('America/Mexico_City');
// Run every 5 minutes. No stock is released merely because local time passed.
$rows=dbTienda()->query("SELECT * FROM pedidos WHERE estado='pendiente_tarjeta' ORDER BY id LIMIT 200")->fetchAll();
foreach($rows as $p){try{
 sincronizarPagoStripe($p);
}catch(Throwable $e){echo 'Pedido '.$p['id'].': sincronización pendiente.\n';}}
