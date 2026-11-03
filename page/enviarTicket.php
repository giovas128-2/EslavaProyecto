<?php
require_once __DIR__.'/../includes/sesion.php';require_once __DIR__.'/../includes/ticketCorreo.php';
header('Cache-Control: no-store, private');
if(!isset($_SESSION['usuario'])){header('Location: login.php');exit;}
try{$p=pedidoVisible((int)($_GET['id']??0));}catch(Throwable $e){http_response_code(404);exit('Pedido no encontrado.');}
$id=(int)$p['id'];$error='';$gmailError=gmailErrorConfiguracion();$ok=$_SESSION['ticket_flash'][$id]??'';unset($_SESSION['ticket_flash'][$id]);$last=null;
if(empty($_SESSION['ticket_tokens'][$id])){$_SESSION['ticket_tokens'][$id]=bin2hex(random_bytes(32));}
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{if(!csrfValido()){throw new RuntimeException('Tu sesión expiró. Actualiza la página.');}$_SESSION['ticket_flash'][$id]=enviarTicketGmail($id,(string)($_POST['solicitud']??''));header('Location: enviarTicket.php?id='.$id,true,303);exit;}
 catch(Throwable $e){$error=$e instanceof PDOException?'Importa actualizar_gmail_graficas.sql para habilitar los envíos.':($e instanceof RuntimeException?$e->getMessage():'No se pudo preparar el envío.');}
}
try{$q=dbTienda()->prepare('SELECT estado,enviado_en,actualizado_en FROM ticket_envios WHERE pedido_id=?');$q->execute([$id]);$last=$q->fetch();}catch(Throwable $e){$error=$error?:'Importa actualizar_gmail_graficas.sql para habilitar los envíos.';}
// A deliberate later visit may request a resend; a POST replay keeps its original token.
if($_SERVER['REQUEST_METHOD']==='GET' && !$ok && ($last['estado']??'')==='enviado'){$_SESSION['ticket_tokens'][$id]=bin2hex(random_bytes(32));}
$titulo='Enviar ticket por Gmail';require __DIR__.'/../includes/shopHeader.php';?>
<main class="section" style="max-width:780px"><a class="back-link" href="pedido.php?id=<?= $id ?>">← Volver al pedido</a><span class="eyebrow">TU COMPROBANTE, A LA MANO</span><h1>Enviar ticket por Gmail</h1><section class="white-card"><h2><?= escapar($p['folio']) ?></h2><p>Enviaremos el ticket PDF adjunto al correo que guardaste al comprar.</p><p><strong>Destinatario:</strong> <?= escapar($p['correo']) ?><br><strong>Total:</strong> <?= dinero((int)$p['total_centavos']) ?></p>
<?php if($p['stripe_modo']==='test'): ?><p class="notice">Pedido de prueba sin cobro real. El correo y el PDF llevarán esta indicación.</p><?php endif; ?>
<?php if($ok): ?><p class="notice" role="status"><?= escapar($ok) ?></p><?php endif; ?><?php if($error): ?><p class="notice error" role="alert"><?= escapar($error) ?></p><?php endif; ?>
<?php if(!ticketEnviable($p)): ?><p class="notice"><?= $p['estado']==='pendiente_tarjeta'?'El envío por Gmail estará disponible cuando Stripe confirme el pago con tarjeta.':'Este pedido no permite enviar el ticket por Gmail en su estado actual.' ?></p><?php else: ?>
<?php if($p['estado']==='pendiente_efectivo'): ?><p class="notice">El pago en efectivo está pendiente. El ticket adjunto indicará que debes pagar al recibir o recoger tu pedido.</p><?php endif; ?>
<?php if($gmailError!==''): ?><p class="notice"><?= escapar($gmailError) ?> Puedes descargar tu ticket PDF mientras se corrige.</p><?php endif; ?>
<?php if(($last['estado']??'')==='enviado'): ?><p class="muted">Último envío aceptado por Gmail: <?= escapar($last['enviado_en']) ?> (Ciudad de México).</p><?php endif; ?>
<form method="post" class="panel-actions" id="gmail-ticket-form"><input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>"><input type="hidden" name="solicitud" value="<?= escapar($_SESSION['ticket_tokens'][$id]) ?>"><button class="button" type="submit" <?= $gmailError===''?'':'disabled' ?>>Enviar ticket por Gmail →</button><a class="button secondary" href="../components/ticket.php?id=<?= $id ?>">Descargar ticket PDF ↓</a></form><p class="muted">El correo se envía desde la cuenta de la tienda. No necesitas iniciar sesión en Gmail aquí.</p>
<?php endif; ?></section></main><script>document.getElementById('gmail-ticket-form')?.addEventListener('submit',function(){const b=this.querySelector('button');b.disabled=true;b.textContent='Enviando ticket…';});</script><?php require __DIR__.'/../includes/shopFooter.php'; ?>
