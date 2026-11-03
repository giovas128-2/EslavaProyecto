<?php
require_once __DIR__.'/../includes/sesion.php';require_once __DIR__.'/../includes/comercio.php';require_once __DIR__.'/../includes/ticketPdf.php';
try{$p=pedidoVisible((int)($_GET['id']??0));
 if($p['estado']==='pendiente_tarjeta'){http_response_code(409);exit('El pago aún no está confirmado. Actualiza el estado del pedido.');}
 $pdf=construirTicket($p,detallesPedido((int)$p['id']));header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="Ticket-'.$p['folio'].'.pdf"');header('Content-Length: '.strlen($pdf));echo $pdf;
}catch(TicketImageException $e){http_response_code(503);echo escapar($e->getMessage());}catch(Throwable $e){http_response_code(404);echo 'No se pudo generar el ticket de este pedido.';}
