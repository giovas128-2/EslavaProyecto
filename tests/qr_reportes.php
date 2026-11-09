<?php
// php tests/qr_reportes.php (requiere PDO SQLite). Datos sintéticos, sin compras reales.
require __DIR__.'/../includes/productoQr.php';
require __DIR__.'/../includes/reportes.php';
function check($ok,string $message): void {if(!$ok)throw new RuntimeException($message);}
function rejects(callable $fn,string $message): void {try{$fn();}catch(RuntimeException $e){return;}catch(InvalidArgumentException $e){return;}throw new RuntimeException($message);}
check(destinoProductoQr(['detalle'=>'12'])==='productoQr.php?detalle=12','Retorno de QR');
foreach(['0','-1','https://otro.test','1&next=https://otro.test',"1\n",[]] as $bad)check(destinoProductoQr(['detalle'=>$bad])===null,'Rechazar retorno inválido');
$token=str_repeat('a',64);check(destinoProductoQr(['carrito'=>$token])!==null,'Token válido');
foreach(['2026-02-30','2026-13-01','texto',''] as $date)rejects(fn()=>filtrosReporte(['desde'=>$date]),'Fecha inválida aceptada');
rejects(fn()=>filtrosReporte(['desde'=>'2026-11-10','hasta'=>'2026-11-01']),'Fechas invertidas');
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('CREATE TABLE pedidos (id INTEGER PRIMARY KEY, folio TEXT,nombre TEXT,usuario_id INTEGER,usuario_origen TEXT,creado_en TEXT,pagado_en TEXT,estado TEXT,metodo TEXT,stripe_modo TEXT,subtotal_centavos INTEGER,iva_centavos INTEGER,iva_porcentaje INTEGER,envio_centavos INTEGER,total_centavos INTEGER)');
$db->exec('CREATE TABLE pedido_detalles (id INTEGER PRIMARY KEY,pedido_id INTEGER,producto_id INTEGER,producto TEXT,cantidad INTEGER,precio_centavos INTEGER)');
$ins=$db->prepare('INSERT INTO pedidos VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
foreach([[1,'2026-11-01 00:00:00','pagado',null,11600],[2,'2026-11-30 23:59:59','entregado',null,26200],[3,'2026-12-01 00:00:00','pagado',null,99900],[4,'2026-11-15 12:00:00','pagado','test',50000],[5,'2026-11-15 12:00:00','pendiente_efectivo',null,50000],[6,'2026-11-15 12:00:00','cancelado',null,60000],[7,'2026-10-31 23:59:59','pagado',null,99900]] as [$id,$date,$state,$mode,$total])$ins->execute([$id,'GX-'.$id,'Cliente de prueba',3,'registroUsuarios',$date,null,$state,'efectivo',$mode,10000,1600,16,0,$total]);
$db->exec("INSERT INTO pedido_detalles VALUES(1,1,8,'Guitarra A',2,5000),(2,1,7,'Guitarra B',1,3000),(3,2,9,'Guitarra C',1,20000)");
$f=filtrosReporte(['desde'=>'2026-11-01','hasta'=>'2026-11-30']);$r=datosReporte($db,$f);
check((int)$r['resumen']['pedidos']===4,'Pedidos del mes, excluyendo pruebas');
check((int)$r['resumen']['cobrado']===37800,'Cobrado no duplica detalles ni incluye pendientes/cancelados/pruebas');
check((int)$r['resumen']['ventas']===2,'Ventas reales');
check((int)$r['resumen']['pendientes']===1,'Pendientes');
check(count($r['detalles'][1])===2,'Pedido de varios productos');
$r=datosReporte($db,array_replace($f,['modo'=>'todos']));check((int)$r['resumen']['pedidos']===5 && (int)$r['resumen']['cobrado']===37800,'Modo todos conserva cobrado real');
$r=datosReporte($db,array_replace($f,['modo'=>'prueba']));check((int)$r['resumen']['pedidos']===1 && (int)$r['resumen']['cobrado']===0,'Solo pruebas no cuenta dinero real');
$r=datosReporte($db,filtrosReporte(['desde'=>'2025-01-01','hasta'=>'2025-01-02']));check((int)$r['resumen']['pedidos']===0 && $r['paginas']===1,'Periodo vacío');
$owner=['id'=>3,'origen'=>'registroUsuarios'];
check(leerProductoQr($db,$owner,['detalle'=>'1'])['cantidad']===2,'Comprador ve cantidad histórica');
rejects(fn()=>leerProductoQr($db,['id'=>4,'origen'=>'registroUsuarios'],['detalle'=>'1']),'Otro comprador no debe acceder');
rejects(fn()=>leerProductoQr($db,['id'=>3,'origen'=>'registrarAdmins'],['detalle'=>'1']),'ID coincidente de otro rol no debe acceder');
$db->exec('CREATE TABLE carrito_qr(token TEXT,usuario_id INTEGER,usuario_origen TEXT,producto_id INTEGER,producto TEXT,cantidad INTEGER,precio_centavos INTEGER,expira_en TEXT)');
$q=$db->prepare('INSERT INTO carrito_qr VALUES(?,?,?,?,?,?,?,?)');$q->execute([$token,3,'registroUsuarios',8,'Guitarra A',4,5000,'9999-01-01 00:00:00']);
check(leerProductoQr($db,$owner,['carrito'=>$token])['cantidad']===4,'QR de carrito entre sesiones');
rejects(fn()=>leerProductoQr($db,['id'=>4,'origen'=>'registroUsuarios'],['carrito'=>$token]),'Carrito de otro usuario');
$db->exec("UPDATE carrito_qr SET expira_en='2000-01-01 00:00:00'");rejects(fn()=>leerProductoQr($db,$owner,['carrito'=>$token]),'QR vencido');
echo "OK: acceso por propietario, retorno seguro, vencimiento, fechas inclusivas, estados, pruebas y sumas.\n";
