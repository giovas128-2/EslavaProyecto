<?php
require_once __DIR__ . '/../includes/adminGuard.php';
$titulo='Bitácora de accesos';$registros=[];$error='';$total=0;
$pagina=max(1,(int)($_GET['pagina'] ?? 1));$filtro=is_string($_GET['intento'] ?? null) ? $_GET['intento'] : '';
if (!in_array($filtro,['exitoso','fallido'],true)) { $filtro=''; }
$where=$filtro ? ' WHERE intento=?' : ''; $params=$filtro ? [$filtro] : [];
try {
 $q=$conexion->prepare('SELECT COUNT(*) FROM bitacora'.$where);$q->execute($params);$total=(int)$q->fetchColumn();
 $paginas=max(1,(int)ceil($total/50));$pagina=min($pagina,$paginas);$offset=($pagina-1)*50;
 $q=$conexion->prepare('SELECT * FROM bitacora'.$where.' ORDER BY id DESC LIMIT 50 OFFSET '.$offset);$q->execute($params);$registros=$q->fetchAll();
} catch(PDOException $e) { $error='No se pudo cargar la bitácora. Importa actualizar_panel.sql y revisa MySQL.'; $paginas=1; }
require __DIR__ . '/../includes/panelHeader.php'; ?>
<p class="panel-note">Entradas correctas, intentos fallidos y cierres de sesión. Horario de Ciudad de México. No se guardan contraseñas.</p>
<?php if($error): ?><p class="alert"><?= escapar($error) ?></p><?php endif; ?>
<form class="panel-tools" method="get"><label>Resultado<select name="intento"><option value="">Todos</option><option value="exitoso" <?= $filtro==='exitoso'?'selected':'' ?>>Exitoso</option><option value="fallido" <?= $filtro==='fallido'?'selected':'' ?>>Fallido</option></select></label><button class="admin-save">Filtrar</button><a href="bitacora.php">Actualizar</a><span><?= $total ?> registros</span></form>
<section class="panel-card table-scroll"><table class="panel-table"><thead><tr><th>ID</th><th>Fecha</th><th>Hora</th><th>Intento</th><th>Usuario / correo ingresado</th><th>Evento</th></tr></thead><tbody>
<?php foreach($registros as $r): ?><tr><td><?= (int)$r['id'] ?></td><td><?= date('d/m/Y',strtotime($r['fecha'])) ?></td><td><?= date('g:i a',strtotime($r['hora'])) ?></td><td><span class="badge <?= $r['intento']==='fallido'?'fail':'' ?>"><?= escapar(ucfirst($r['intento'])) ?></span></td><td><?= escapar($r['usuario'] ?: '(sin usuario)') ?><small><?= escapar($r['origen'] ?? 'Cuenta no verificada') ?></small></td><td><?= $r['evento']==='salida'?'Salida':'Entrada' ?></td></tr><?php endforeach; ?>
<?php if(!$registros): ?><tr><td colspan="6" class="empty">No hay eventos para este filtro.</td></tr><?php endif; ?></tbody></table></section>
<div class="panel-tools"><?php if($pagina>1): ?><a href="?pagina=<?= $pagina-1 ?>&amp;intento=<?= escapar($filtro) ?>">← Anterior</a><?php endif; ?><span>Página <?= $pagina ?> de <?= $paginas ?></span><?php if($pagina<$paginas): ?><a href="?pagina=<?= $pagina+1 ?>&amp;intento=<?= escapar($filtro) ?>">Siguiente →</a><?php endif; ?></div>
<p class="panel-note">La salida se registra al pulsar “Cerrar sesión”. Cerrar la pestaña o perder la conexión no permite confirmar una salida. Los intentos fallidos conservan el correo escrito, aunque la cuenta no exista.</p>
<?php require __DIR__ . '/../includes/panelFooter.php'; ?>
