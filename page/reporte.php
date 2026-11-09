<?php
require_once __DIR__.'/../includes/adminGuard.php';
require_once __DIR__.'/../includes/comercio.php';
require_once __DIR__.'/../includes/reportes.php';
$error='';$data=null;$f=filtrosReporte([]);
try {$f=filtrosReporte($_GET);$data=datosReporte($conexion,$f);}
catch(InvalidArgumentException $e){http_response_code(422);$error=$e->getMessage();}
catch(Throwable $e){http_response_code(503);$error='No se pudo cargar el reporte. Intenta de nuevo más tarde.';error_log('Guitarixta reporte: '.$e->getCode());}
$estados=[''=>'Todos los estados','pagado'=>'Pagado','entregado'=>'Entregado','pendiente_efectivo'=>'Pendiente de efectivo','pendiente_tarjeta'=>'Pendiente de tarjeta','cancelado'=>'Cancelado','expirado'=>'Expirado'];
$titulo='Reporte de ventas y pedidos';require __DIR__.'/../includes/panelHeader.php';
?>
<link rel="stylesheet" href="../assets/css/graficas.css">
<div class="gx-analytics">
<div class="gx-report-intro"><div><span class="eyebrow">RESULTADOS DE TU TIENDA</span><h2>De una fecha a otra</h2><p>Consulta tus pedidos y distingue las ventas cobradas de los pagos pendientes.</p></div><span class="gx-live">● Importes en MXN</span></div>
<form method="get" class="gx-filters">
<label>Desde<input type="date" name="desde" value="<?= escapar($f['desde']) ?>" required></label>
<label>Hasta<input type="date" name="hasta" value="<?= escapar($f['hasta']) ?>" required></label>
<label>Estado<select name="estado"><?php foreach($estados as $key=>$label): ?><option value="<?= escapar($key) ?>" <?= $f['estado']===$key?'selected':'' ?>><?= escapar($label) ?></option><?php endforeach; ?></select></label>
<label>Tipo de pedido<select name="modo"><?php foreach(['reales'=>'Solo reales','prueba'=>'Solo pruebas','todos'=>'Reales y pruebas'] as $key=>$label): ?><option value="<?= $key ?>" <?= $f['modo']===$key?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></label>
<div class="gx-filter-actions"><button class="admin-save" type="submit">Consultar reporte</button><a class="admin-cancel" href="reporte.php">Restablecer</a></div>
</form>
<?php if($error): ?><p class="alert" role="alert"><?= escapar($error) ?></p><?php else: $r=$data['resumen']; ?>
<p class="gx-footnote">Periodo: <?= escapar(date('d/m/Y',strtotime($f['desde']))) ?> al <?= escapar(date('d/m/Y',strtotime($f['hasta']))) ?>, ambos días incluidos. Se filtra por fecha de creación del pedido, hora de Ciudad de México.</p>
<div class="gx-report-status" role="status"><?= (int)$r['pedidos'] ?> pedidos encontrados</div>
<section class="gx-kpis" aria-label="Resumen del periodo">
<article><span>Pedidos del periodo</span><strong><?= (int)$r['pedidos'] ?></strong><small>Según los filtros seleccionados</small></article>
<article><span>Ventas cobradas</span><strong><?= (int)$r['ventas'] ?></strong><small>Pedidos reales pagados o entregados</small></article>
<article><span>Total cobrado real</span><strong><?= dinero((int)$r['cobrado']) ?></strong><small>Incluye IVA y envío; excluye pruebas</small></article>
<article><span>Pedidos pendientes</span><strong><?= (int)$r['pendientes'] ?></strong><small>Pendientes de efectivo o tarjeta</small></article>
</section>
<section class="gx-chart-card"><div class="gx-chart-heading"><div><span class="eyebrow">VENTAS Y PEDIDOS</span><h3>Detalle del periodo</h3><p>Los cancelados, expirados y pendientes no se suman al total cobrado.</p></div></div>
<div class="gx-table-scroll" tabindex="0" role="region" aria-label="Tabla de pedidos; desplázate horizontalmente para ver las columnas"><table class="gx-table"><thead><tr><th scope="col">Pedido / usuario</th><th scope="col">Productos y cantidades</th><th scope="col">Fecha y hora</th><th scope="col">Estado / pago</th><th scope="col">Subtotal</th><th scope="col">IVA</th><th scope="col">Envío</th><th scope="col">Total</th></tr></thead><tbody>
<?php foreach($data['pedidos'] as $p): ?><tr>
<td><strong><?= escapar($p['folio']) ?></strong><span><?= escapar($p['nombre']) ?></span><small>Cuenta <?= escapar($p['usuario_origen']) ?> #<?= (int)$p['usuario_id'] ?></small><a class="gx-order-link" href="pedidosAdmin.php?id=<?= (int)$p['id'] ?>">Ver pedido →</a></td>
<td><?php foreach($data['detalles'][$p['id']]??[] as $d): ?><strong><?= escapar($d['producto']) ?></strong><small><?= (int)$d['cantidad'] ?> piezas × <?= dinero((int)$d['precio_centavos']) ?></small><?php endforeach; ?></td>
<td><strong><?= escapar(date('d/m/Y H:i',strtotime($p['creado_en']))) ?></strong><small><?= $p['pagado_en']?'Pago: '.escapar(date('d/m/Y H:i',strtotime($p['pagado_en']))):'Pago sin confirmar' ?></small></td>
<td><span class="gx-state"><?= escapar($estados[$p['estado']]??$p['estado']) ?></span><small><?= escapar(ucfirst($p['metodo'])) ?><?= $p['stripe_modo']==='test'?' · PRUEBA SIN COBRO REAL':'' ?></small></td>
<td class="gx-total-cell"><?= dinero((int)$p['subtotal_centavos']) ?></td><td class="gx-total-cell"><?= dinero((int)$p['iva_centavos']) ?></td><td class="gx-total-cell"><?= dinero((int)$p['envio_centavos']) ?></td><td class="gx-total-cell"><?= dinero((int)$p['total_centavos']) ?></td></tr><?php endforeach; ?>
<?php if(!$data['pedidos']): ?><tr><td colspan="8" class="gx-empty">No hay pedidos en este periodo con los filtros seleccionados.</td></tr><?php endif; ?>
</tbody></table></div>
<nav class="gx-pagination" aria-label="Páginas del reporte">
<?php if($data['pagina']>1): ?><a class="admin-cancel" href="?<?= escapar(http_build_query(array_replace($f,['pagina'=>$data['pagina']-1]))) ?>">← Anterior</a><?php endif; ?>
<span>Página <?= $data['pagina'] ?> de <?= $data['paginas'] ?></span>
<?php if($data['pagina']<$data['paginas']): ?><a class="admin-cancel" href="?<?= escapar(http_build_query(array_replace($f,['pagina'=>$data['pagina']+1]))) ?>">Siguiente →</a><?php endif; ?>
</nav><p class="gx-footnote">El resumen corresponde a todos los resultados del filtro, no solamente a esta página. Los pagos de prueba se identifican en la tabla y nunca cuentan como ventas cobradas reales.</p></section>
<?php endif; ?></div>
<?php require __DIR__.'/../includes/panelFooter.php'; ?>
