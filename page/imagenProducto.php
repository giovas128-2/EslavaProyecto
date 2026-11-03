<?php
require_once __DIR__ . '/../includes/adminGuard.php';
$id=filter_var($_GET['id'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if (!$id) { http_response_code(404); exit('Producto no encontrado.'); }
try { $q=$conexion->prepare('SELECT id,producto,OCTET_LENGTH(imagen) AS bytes,SUBSTRING(imagen,1,512) AS muestra,imagen_tipo FROM productos WHERE id=?'); $q->execute([$id]); $producto=$q->fetch(); }
catch (PDOException $e) { http_response_code(503); exit('No se pudo consultar la imagen.'); }
if (!$producto) { http_response_code(404); exit('Producto no encontrado.'); }
$titulo='Imagen y código binario'; require __DIR__ . '/../includes/panelHeader.php';
?>
<a class="panel-link" href="productos.php">← Volver a productos</a><section class="panel-card"><h2><?= escapar($producto['producto']) ?></h2><img class="product-large" src="../components/imagenProducto.php?id=<?= $id ?>" alt="<?= escapar($producto['producto']) ?>">
<p class="panel-note">Esta imagen está guardada como bytes en la columna <strong>imagen (MEDIUMBLOB)</strong>, no como una URL. El navegador recibe esos bytes para mostrarla. Cada grupo de 8 bits representa un byte del archivo original.</p>
<p><?= number_format((int)$producto['bytes']) ?> bytes · <?= escapar($producto['imagen_tipo']) ?></p>
<h3>Representación binaria (ceros y unos)</h3><p class="panel-note">Vista de los primeros <?= strlen($producto['muestra']) ?> bytes. Puedes descargar el binario completo.</p>
<pre class="binary"><?php for($i=0;$i<strlen($producto['muestra']);$i++) { echo sprintf('%08b',ord($producto['muestra'][$i])).(($i+1)%8===0 ? "\n" : ' '); } ?></pre>
<a class="admin-save" href="../components/imagenProducto.php?id=<?= $id ?>&amp;binario=1">Descargar todos los ceros y unos (.txt)</a>
</section><?php require __DIR__ . '/../includes/panelFooter.php'; ?>
