<?php
require_once __DIR__ . '/../includes/adminGuard.php';
require_once __DIR__ . '/../includes/comercio.php';
function regresarProducto(string $mensaje): void { $_SESSION['panel_mensaje']=$mensaje; header('Location: ../page/productos.php',true,303); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
if (!csrfValido()) { regresarProducto('La sesión expiró. Actualiza la página.'); }
$accion = $_POST['accion'] ?? '';
$id=filter_var($_POST['id'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
try {
 if (!in_array($accion,['crear','editar','eliminar'],true)) { throw new RuntimeException('Acción no válida.'); }
 if ($accion !== 'crear' && !$id) { throw new RuntimeException('Producto no válido.'); }
 if ($accion === 'eliminar') {
   $exists=$conexion->prepare('SELECT id FROM pedido_detalles WHERE producto_id=? LIMIT 1');$exists->execute([$id]);
   if($exists->fetch()){regresarProducto('Este producto tiene pedidos. Desactiva su venta; conserva el registro para el historial y las reservas.');}
   $q=$conexion->prepare('DELETE FROM productos WHERE id=?');$q->execute([$id]);
   regresarProducto($q->rowCount() ? 'Producto eliminado.' : 'El producto ya no existe.');
 }
 $nombre=is_string($_POST['producto'] ?? null) ? trim($_POST['producto']) : '';
 $stock=filter_var($_POST['stock'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>2147483647]]);
 if ($nombre === '' || strlen($nombre)>150 || $stock === false) { throw new RuntimeException('Revisa el nombre (hasta 150 bytes) y el stock (entero igual o mayor a cero).'); }
 $precio=is_string($_POST['precio']??null)?$_POST['precio']:'0';$priceCents=centavos($precio);$activo=($_POST['activo']??'')==='1'?1:0;
 $categoria=is_string($_POST['categoria']??null)?$_POST['categoria']:'Guitarras';$desc=is_string($_POST['descripcion']??null)?trim($_POST['descripcion']):'';
 if(!in_array($categoria,['Clásicas','Acústicas','Eléctricas','Guitarras'],true) || strlen($desc)>600 || ($activo && $priceCents<1000)){throw new RuntimeException('Revisa la descripción y categoría. Para publicar, el precio debe ser de al menos $10 MXN.');}
 $binario=null; $mime=null; $archivo=$_FILES['imagen'] ?? null;
 if ($archivo && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
   if (is_array($archivo['error']) || $archivo['error'] !== UPLOAD_ERR_OK) { throw new RuntimeException('No se pudo subir la imagen. Revisa su tamaño y los límites de PHP.'); }
   if (!is_uploaded_file($archivo['tmp_name']) || filesize($archivo['tmp_name'])>3*1024*1024) { throw new RuntimeException('La imagen debe pesar como máximo 3 MB.'); }
   $info=@getimagesize($archivo['tmp_name']);
   $mime=(new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
   if (!$info || !in_array($mime,['image/jpeg','image/png','image/webp'],true) || $info['mime'] !== $mime) { throw new RuntimeException('Selecciona una imagen JPG, PNG o WebP válida.'); }
   $binario=file_get_contents($archivo['tmp_name']);
 }
 if ($accion === 'crear') {
   if ($binario === null) { throw new RuntimeException('Selecciona la imagen del producto.'); }
   $conexion->beginTransaction();
   $q=$conexion->prepare('INSERT INTO productos (producto,stock,imagen,imagen_tipo,fecha,hora) VALUES (?,?,?,?,?,?)');
   $q->execute([$nombre,$stock,$binario,$mime,date('Y-m-d'),date('H:i:s')]);$id=(int)$conexion->lastInsertId();
 } else {
   $conexion->beginTransaction();
   $q=$conexion->prepare('SELECT id,stock FROM productos WHERE id=? FOR UPDATE');$q->execute([$id]);
   $actual=$q->fetch();if(!$actual){throw new RuntimeException('El producto ya no existe.');}
   if((int)$actual['stock']!==(int)($_POST['stock_original']??-1)){throw new RuntimeException('El stock cambió mientras editabas. Abre de nuevo el producto para evitar sobrescribir una compra reciente.');}
   if ($binario === null) { $q=$conexion->prepare('UPDATE productos SET producto=?,stock=? WHERE id=?');$q->execute([$nombre,$stock,$id]); }
   else { $q=$conexion->prepare('UPDATE productos SET producto=?,stock=?,imagen=?,imagen_tipo=? WHERE id=?');$q->execute([$nombre,$stock,$binario,$mime,$id]); }
 }
 $conexion->prepare("UPDATE productos SET precio=?,categoria=?,descripcion=?,activo=?,codigo=COALESCE(NULLIF(codigo,''),?) WHERE id=?")->execute([number_format($priceCents/100,2,'.',''),$categoria,$desc,$activo,'GX'.str_pad((string)$id,8,'0',STR_PAD_LEFT),$id]);
 $conexion->commit();
 regresarProducto('Producto guardado correctamente.');
} catch (PDOException $e) { if($conexion->inTransaction()){$conexion->rollBack();} error_log('Productos: '.$e->getCode()); regresarProducto('No se pudo guardar. Revisa MySQL, el esquema y max_allowed_packet (mínimo 8 MB).'); }
catch (RuntimeException $e) { if($conexion->inTransaction()){$conexion->rollBack();} regresarProducto($e->getMessage()); }
