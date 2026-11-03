<?php
require_once __DIR__.'/comercio.php';require_once __DIR__.'/ticketPdf.php';
require_once __DIR__.'/vendor/phpmailer/src/Exception.php';require_once __DIR__.'/vendor/phpmailer/src/PHPMailer.php';require_once __DIR__.'/vendor/phpmailer/src/SMTP.php';
function gmailErrorConfiguracion(): string {
 $c=require __DIR__.'/gmail_config.php';
 if(!(bool)filter_var($c['usuario'],FILTER_VALIDATE_EMAIL) || strlen(str_replace(' ','',$c['password_aplicacion']))!==16){return 'El envío por Gmail aún no está configurado. El administrador debe revisar la cuenta y la contraseña de aplicación.';}
 if(!extension_loaded('openssl')){return 'Gmail requiere OpenSSL para conectarse con TLS. Habilita la extensión openssl en el php.ini de Apache y reinicia Apache.';}
 return '';
}
function gmailConfigurado(): bool {return gmailErrorConfiguracion()==='';}
function ticketPagado(array $p): bool {return in_array($p['estado'],['pagado','entregado'],true);}
function ticketEnviable(array $p): bool {return ticketPagado($p) || ($p['metodo']==='efectivo' && $p['estado']==='pendiente_efectivo');}
function mensajeTicket(array $p,array $items): \PHPMailer\PHPMailer\PHPMailer {
 if(!ticketEnviable($p)){throw new RuntimeException('El ticket por correo no está disponible para este estado del pedido.');}
 $configError=gmailErrorConfiguracion();if($configError!==''){throw new RuntimeException($configError);}
 $c=require __DIR__.'/gmail_config.php';
 if(!filter_var($p['correo'],FILTER_VALIDATE_EMAIL)){throw new RuntimeException('El correo guardado en el pedido no es válido.');}
 $m=new \PHPMailer\PHPMailer\PHPMailer(true);$m->isSMTP();$m->Host='smtp.gmail.com';$m->Port=587;$m->SMTPSecure=\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;$m->SMTPAuth=true;
 $m->Username=$c['usuario'];$m->Password=str_replace(' ','',$c['password_aplicacion']);$m->Timeout=15;$m->getSMTPInstance()->Timelimit=30;$m->SMTPDebug=0;$m->CharSet='UTF-8';$m->XMailer='';
 $m->setFrom($c['usuario'],$c['nombre']);$m->addAddress($p['correo'],$p['nombre']);
 $test=$p['stripe_modo']==='test';$m->Subject=($test?'[PRUEBA SIN COBRO REAL] ':'').'Tu ticket Guitarixta · '.$p['folio'];
 $pendiente=$p['estado']==='pendiente_efectivo';
 $m->Body='Hola, '.$p['nombre'].".\n\n".($test?"Este pedido corresponde a una prueba; no se realizó un cobro real.\n\n":'').($pendiente?"El pago de este pedido en efectivo está pendiente. Paga al recibirlo o recogerlo.\n\n":'').'Adjuntamos el ticket de tu pedido '.$p['folio'].".\n".'Total: '.dinero((int)$p['total_centavos'])."\n".'Método de pago: '.($p['metodo']==='tarjeta'?'Tarjeta':'Efectivo')."\n".'Entrega: '.($p['entrega']==='domicilio'?'A domicilio':'Recoger en tienda')."\n\nGracias por comprar en Guitarixta.\nEste ticket no es una factura fiscal.";
 $m->addStringAttachment(construirTicket($p,$items),'Ticket-'.$p['folio'].'.pdf','base64','application/pdf');return $m;
}
function enviarTicketGmail(int $id,string $token): string {
 $p=pedidoVisible($id); // Authorizes the current owner or an existing administrator.
 if(!ticketEnviable($p)){throw new RuntimeException('El ticket por correo no está disponible para este estado del pedido.');}
 if(!preg_match('/^[a-f0-9]{64}$/',$token) || !hash_equals($_SESSION['ticket_tokens'][$id]??'',$token)){throw new RuntimeException('Actualiza la página de envío.');}
 $m=mensajeTicket($p,detallesPedido($id));$db=dbTienda();$now=date('Y-m-d H:i:s');$day=date('Y-m-d');
 $db->beginTransaction();
 try{
  // Order row serializes the first insert as well as subsequent resend attempts.
  $q=$db->prepare('SELECT metodo,estado FROM pedidos WHERE id=? FOR UPDATE');$q->execute([$id]);$actual=$q->fetch();
  if(!$actual || !ticketEnviable($actual)){throw new RuntimeException('El pedido ya no permite enviar su ticket por correo.');}
  $q=$db->prepare('SELECT * FROM ticket_envios WHERE pedido_id=? FOR UPDATE');$q->execute([$id]);$last=$q->fetch();
  if($last){
   if($last['solicitud']===$token && $last['estado']==='enviado'){$db->commit();return 'Este envío ya fue confirmado por Gmail. Revisa tu bandeja de entrada y spam.';}
   if(in_array($last['estado'],['enviando','incierto'],true)){throw new RuntimeException('Hay un envío en proceso o pendiente de confirmar. Revisa tu correo; el administrador puede comprobarlo en Gmail antes de permitir otro intento.');}
   if(strtotime($last['actualizado_en'])>time()-300){throw new RuntimeException('Espera 5 minutos antes de solicitar otro envío.');}
   $count=$last['dia']===$day?(int)$last['intentos_dia']:0;
   if($count>=3){throw new RuntimeException('Ya se solicitaron 3 envíos de este pedido hoy. Puedes descargar el PDF.');}
   $db->prepare("UPDATE ticket_envios SET destinatario=?,estado='enviando',solicitud=?,dia=?,intentos_dia=?,actualizado_en=?,message_id=NULL WHERE pedido_id=?")->execute([$p['correo'],$token,$day,$count+1,$now,$id]);
  }else{$db->prepare("INSERT INTO ticket_envios (pedido_id,destinatario,estado,solicitud,dia,intentos_dia,actualizado_en) VALUES (?,?,'enviando',?,?,1,?)")->execute([$id,$p['correo'],$token,$day,$now]);}
  $db->commit();
 }catch(Throwable $e){if($db->inTransaction()){$db->rollBack();}throw $e;}
 $transmitiendo=false;
 try{
  $m->preSend();if(!$m->smtpConnect()){throw new RuntimeException('SMTP connection failed');}
  $transmitiendo=true;if(!$m->postSend()){throw new RuntimeException('SMTP send failed');}
  $db->prepare("UPDATE ticket_envios SET estado='enviado',enviado_en=?,actualizado_en=?,message_id=? WHERE pedido_id=? AND solicitud=?")->execute([date('Y-m-d H:i:s'),date('Y-m-d H:i:s'),$m->getLastMessageID(),$id,$token]);
  return 'Gmail aceptó el ticket para envío a '.$p['correo'].'. Revisa tu bandeja de entrada y spam.';
 }catch(Throwable $e){
  try{$db->prepare('UPDATE ticket_envios SET estado=?,actualizado_en=? WHERE pedido_id=? AND solicitud=?')->execute([$transmitiendo?'incierto':'error',date('Y-m-d H:i:s'),$id,$token]);}catch(Throwable $ignored){}
  error_log('Guitarixta: ticket SMTP no confirmado, pedido '.$id.'.');
  throw new RuntimeException($transmitiendo?'No se pudo confirmar si Gmail recibió el mensaje. Revisa tu correo antes de pedir otro envío.':'No se pudo conectar o autenticar con Gmail. Revisa la cuenta, la contraseña de aplicación y la conexión.');
 }finally{$m->smtpClose();}
}
