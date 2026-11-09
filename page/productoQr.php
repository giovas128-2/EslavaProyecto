<?php
require_once __DIR__.'/../includes/sesion.php';
require_once __DIR__.'/../includes/productoQr.php';
$destino=destinoProductoQr($_GET);
if (!$destino) {http_response_code(404);exit('QR no válido.');}
if (!isset($_SESSION['usuario'])) {$_SESSION['despues_login']=$destino;header('Location: login.php');exit;}
$error='';$item=null;
try {$item=leerProductoQr(dbTienda(),usuarioCompra(),$_GET);}
catch (PDOException $e) {http_response_code(503);$error='No se pudo cargar el producto. Inténtalo de nuevo más tarde.';}
catch (RuntimeException $e) {http_response_code(404);$error='Este QR no está disponible para tu cuenta o ya venció. Inicia sesión con la cuenta que creó el carrito o el pedido.';}
catch (Throwable $e) {http_response_code(503);$error='No se pudo cargar el producto. Inténtalo de nuevo más tarde.';}
$titulo='Tu producto';require __DIR__.'/../includes/shopHeader.php';
?>
<main class="section qr-product-page">
<span class="eyebrow">TU SELECCIÓN · ACCESO PRIVADO</span>
<h1>Tu producto</h1>
<?php if($error): ?><p class="notice error" role="alert"><?= escapar($error) ?></p><p class="muted">Puedes cerrar sesión desde el icono de tu cuenta y volver a escanear el QR.</p>
<?php else: $esPedido=isset($item['pedido_id']);$importe=(int)$item['cantidad']*(int)$item['precio_centavos']; ?>
<section class="white-card qr-product-card">
<img class="qr-product-photo" src="../components/productoFoto.php?id=<?= (int)$item['producto_id'] ?>" alt="<?= escapar($item['producto']) ?>">
<div><span class="eyebrow"><?= $esPedido?'DETALLE DE COMPRA':'SELECCIÓN DEL CARRITO' ?></span><h2><?= escapar($item['producto']) ?></h2>
<div class="total-line"><span>Stock (cantidad <?= $esPedido?'comprada':'seleccionada' ?>)</span><strong><?= (int)$item['cantidad'] ?> piezas</strong></div>
<div class="total-line"><span>Precio unitario</span><strong><?= dinero((int)$item['precio_centavos']) ?></strong></div>
<div class="total-line grand-total"><span>Precio total del producto</span><strong><?= dinero($importe) ?></strong></div>
<p class="muted">Importe de las piezas de este producto, antes de IVA y envío. La cantidad mostrada no es el inventario disponible.</p>
<?php if($esPedido): ?><p class="status-pill"><?= escapar(str_replace('_',' ',$item['estado'])) ?></p><p class="muted">Pedido <?= escapar($item['folio']) ?> · <?= escapar($item['creado_en']) ?></p><a class="button" href="pedido.php?id=<?= (int)$item['pedido_id'] ?>">Ver pedido e impuestos →</a>
<?php else: ?><p class="notice">Vista previa guardada el <?= escapar($item['actualizado_en']) ?>. No confirma una compra ni reserva existencias. El precio y la disponibilidad se revisan al comprar.</p><p class="muted">Este QR vence el <?= escapar($item['expira_en']) ?>. Al actualizar el carrito en el dispositivo de origen se actualiza esta selección.</p><a class="button secondary" href="tienda.php">Volver a la tienda</a><?php endif; ?>
</div></section><?php endif; ?></main>
<?php require __DIR__.'/../includes/shopFooter.php'; ?>
