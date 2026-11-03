<?php
require_once __DIR__ . '/../includes/adminGuard.php';
$titulo = 'Productos / guitarras'; $error = ''; $productos = []; $editar = null;
try {
 $productos = $conexion->query('SELECT id,producto,stock,precio,activo,fecha,hora FROM productos ORDER BY id DESC')->fetchAll();
 if (isset($_GET['editar'])) { $q=$conexion->prepare('SELECT id,producto,stock,precio,activo,descripcion,categoria FROM productos WHERE id=?'); $q->execute([(int)$_GET['editar']]); $editar=$q->fetch() ?: null; }
} catch (PDOException $e) { $error='No se pudo cargar el catálogo. Importa actualizar_tienda.sql y revisa MySQL.'; }
require __DIR__ . '/../includes/panelHeader.php';
?>
<p class="panel-note">Inventario de guitarras. Pulsa una fotografía para abrirla y consultar su contenido binario.</p>
<?php if ($error): ?><p class="alert"><?= escapar($error) ?></p><?php endif; ?>
<div class="panel-tools"><a href="productos.php?nuevo=1#formulario" class="admin-save">+ Crear nuevo producto</a><span><?= count($productos) ?> productos</span></div>
<?php if (isset($_GET['nuevo']) || $editar): ?>
<section class="panel-card" id="formulario"><h2><?= $editar ? 'Editar producto' : 'Nuevo producto' ?></h2>
<form class="panel-form" method="post" action="../components/gestionarProductos.php" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>"><input type="hidden" name="accion" value="<?= $editar ? 'editar' : 'crear' ?>"><input type="hidden" name="id" value="<?= (int)($editar['id'] ?? 0) ?>">
<input type="hidden" name="stock_original" value="<?= (int)($editar['stock'] ?? 0) ?>"><label>Producto<input required maxlength="150" name="producto" value="<?= escapar($editar['producto'] ?? '') ?>"></label>
<label>Stock<input type="number" required name="stock" min="0" max="2147483647" value="<?= (int)($editar['stock'] ?? 0) ?>"></label>
<label>Precio antes de IVA (MXN)<input name="precio" type="number" min="0" max="99999999.99" step="0.01" required value="<?= escapar($editar['precio'] ?? '0.00') ?>"></label>
<label>Categoría<select name="categoria"><?php foreach(['Clásicas','Acústicas','Eléctricas','Guitarras'] as $cat): ?><option <?= ($editar['categoria']??'')===$cat?'selected':'' ?>><?= $cat ?></option><?php endforeach; ?></select></label>
<label class="full">Descripción<input name="descripcion" maxlength="600" value="<?= escapar($editar['descripcion'] ?? '') ?>"></label>
<label class="full"><span><input type="checkbox" name="activo" value="1" <?= !empty($editar['activo'])?'checked':'' ?> style="width:auto"> Disponible para venta (verifica precio y existencias reales)</span></label>
<label class="full">Imagen (JPG, PNG o WebP, máximo 3 MB)<input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" <?= $editar ? '' : 'required' ?>></label>
<?php if ($editar): ?><p class="panel-note full">Si no seleccionas otra imagen, se conservará la actual. La fecha y hora corresponden a la creación del producto.</p><?php endif; ?>
<div class="full panel-actions"><button class="admin-save">Guardar producto</button><a class="admin-cancel" href="productos.php">Cancelar</a></div>
</form></section><?php endif; ?>
<section class="panel-card table-scroll"><table class="panel-table"><thead><tr><th>ID</th><th>Producto</th><th>Stock</th><th>Precio sin IVA / venta</th><th>Imagen</th><th>Fecha</th><th>Hora</th><th>Acciones</th></tr></thead><tbody>
<?php foreach($productos as $producto): ?><tr><td><?= (int)$producto['id'] ?></td><td><?= escapar($producto['producto']) ?></td><td><?= (int)$producto['stock'] ?></td><td>$<?= number_format((float)$producto['precio'],2) ?> MXN<small><?= $producto['activo']?'Publicado':'Oculto para compra' ?></small></td><td><a href="imagenProducto.php?id=<?= (int)$producto['id'] ?>"><img class="product-thumb" src="../components/imagenProducto.php?id=<?= (int)$producto['id'] ?>" alt="<?= escapar($producto['producto']) ?>" loading="lazy"></a></td><td><?= date('d/m/Y',strtotime($producto['fecha'])) ?></td><td><?= date('g:i a',strtotime($producto['hora'])) ?></td><td><a class="admin-edit" href="productos.php?editar=<?= (int)$producto['id'] ?>#formulario">Editar</a><form class="inline" method="post" action="../components/gestionarProductos.php" onsubmit="return confirm('¿Eliminar este producto y su imagen?')"><input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= (int)$producto['id'] ?>"><button class="admin-delete">Eliminar</button></form></td></tr><?php endforeach; ?>
<?php if (!$productos): ?><tr><td colspan="8" class="empty">Aún no hay productos. Crea uno o importa productos_ejemplo.sql.</td></tr><?php endif; ?>
</tbody></table></section>
<?php require __DIR__ . '/../includes/panelFooter.php'; ?>
