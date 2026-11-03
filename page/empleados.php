<?php
require_once __DIR__ . '/../includes/adminGuard.php';
$titulo='Configuración de empleados';$empleados=[];$editar=null;$error='';
try { $empleados=$conexion->query('SELECT id,nombre,correoElectronico,puesto,telefono FROM empleados ORDER BY nombre')->fetchAll();
 if(isset($_GET['editar'])) { $q=$conexion->prepare('SELECT id,nombre,correoElectronico,puesto,telefono FROM empleados WHERE id=?');$q->execute([(int)$_GET['editar']]);$editar=$q->fetch() ?: null; }
} catch(PDOException $e) { $error='Importa actualizar_panel.sql para habilitar empleados.'; }
require __DIR__ . '/../includes/panelHeader.php'; ?>
<div class="panel-tools"><a class="panel-link" href="admin.php">← Usuarios y administradores</a><a class="admin-save" href="empleados.php?nuevo=1#formulario">+ Agregar empleado</a></div>
<p class="panel-note">Los empleados pueden iniciar sesión con su correo y contraseña. La administración de cuentas, bitácora y productos es exclusiva de los administradores.</p>
<?php if($error): ?><p class="alert"><?= escapar($error) ?></p><?php endif; ?>
<?php if(isset($_GET['nuevo']) || $editar): ?><section class="panel-card" id="formulario"><h2><?= $editar?'Editar empleado':'Nuevo empleado' ?></h2><form class="panel-form" action="../components/gestionarEmpleados.php" method="post">
<input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>"><input type="hidden" name="accion" value="<?= $editar?'editar':'crear' ?>"><input type="hidden" name="id" value="<?= (int)($editar['id'] ?? 0) ?>">
<label>Nombre completo<input required maxlength="100" name="nombre" value="<?= escapar($editar['nombre'] ?? '') ?>"></label><label>Correo electrónico<input type="email" required maxlength="254" name="correo" value="<?= escapar($editar['correoElectronico'] ?? '') ?>"></label><label>Puesto<input required maxlength="100" name="puesto" value="<?= escapar($editar['puesto'] ?? '') ?>"></label><label>Teléfono<input type="tel" maxlength="25" name="telefono" value="<?= escapar($editar['telefono'] ?? '') ?>"></label><label class="full"><?= $editar?'Nueva contraseña (vacía para conservar la actual)':'Contraseña' ?><input type="password" name="contrasena" minlength="8" maxlength="72" autocomplete="new-password" <?= $editar?'':'required' ?>></label>
<div class="full panel-actions"><button class="admin-save">Guardar empleado</button><a class="admin-cancel" href="empleados.php">Cancelar</a></div></form></section><?php endif; ?>
<section class="panel-card table-scroll"><table class="panel-table"><thead><tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Puesto</th><th>Teléfono</th><th>Acciones</th></tr></thead><tbody>
<?php foreach($empleados as $r): ?><tr><td><?= (int)$r['id'] ?></td><td><?= escapar($r['nombre']) ?></td><td><?= escapar($r['correoElectronico']) ?></td><td><?= escapar($r['puesto']) ?></td><td><?= escapar($r['telefono']) ?></td><td><a class="admin-edit" href="empleados.php?editar=<?= (int)$r['id'] ?>#formulario">Editar</a><form class="inline" action="../components/gestionarEmpleados.php" method="post" onsubmit="return confirm('¿Eliminar este empleado?')"><input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="admin-delete">Eliminar</button></form></td></tr><?php endforeach; ?>
<?php if(!$empleados): ?><tr><td colspan="6" class="empty">Todavía no hay empleados registrados.</td></tr><?php endif; ?></tbody></table></section>
<?php require __DIR__ . '/../includes/panelFooter.php'; ?>
