<?php
require_once __DIR__ . '/../includes/adminGuard.php';
function regresarEmpleado(string $mensaje): void { $_SESSION['panel_mensaje']=$mensaje;header('Location: ../page/empleados.php',true,303);exit; }
function campoEmpleado(string $key): string { return is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : ''; }
if($_SERVER['REQUEST_METHOD']!=='POST') {http_response_code(405);exit;}
if(!csrfValido()) {regresarEmpleado('La sesión expiró. Actualiza la página.');}
$accion=campoEmpleado('accion');$id=filter_var($_POST['id'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
try {
 if(!in_array($accion,['crear','editar','eliminar'],true) || ($accion!=='crear' && !$id)) {throw new RuntimeException('Acción no válida.');}
 if($accion==='eliminar') {$q=$conexion->prepare('DELETE FROM empleados WHERE id=?');$q->execute([$id]);regresarEmpleado($q->rowCount()?'Empleado eliminado.':'El empleado ya no existe.');}
 $nombre=campoEmpleado('nombre');$correo=campoEmpleado('correo');$puesto=campoEmpleado('puesto');$telefono=campoEmpleado('telefono');
 $clave=is_string($_POST['contrasena'] ?? null) ? $_POST['contrasena'] : '';
 if($nombre==='' || strlen($nombre)>100 || $puesto==='' || strlen($puesto)>100 || strlen($telefono)>25 || !filter_var($correo,FILTER_VALIDATE_EMAIL) || strlen($correo)>254) {throw new RuntimeException('Revisa el nombre, correo, puesto y teléfono.');}
 if(($accion==='crear' || $clave!=='') && (strlen($clave)<8 || strlen($clave)>72)) {throw new RuntimeException('La contraseña debe tener entre 8 y 72 bytes.');}
 foreach(['empleados'=>'correoElectronico','registrarAdmins'=>'correoElectronico','registroUsuarios'=>'correoElectronico','login'=>'correo'] as $tabla=>$columna) {
  $sql='SELECT id FROM `'.$tabla.'` WHERE `'.$columna.'`=?';$param=[$correo];
  if($tabla==='empleados' && $accion==='editar') {$sql.=' AND id<>?';$param[]=$id;}
  $q=$conexion->prepare($sql);$q->execute($param);if($q->fetch()) {throw new RuntimeException('El correo ya está utilizado por otra cuenta.');}
 }
 if($accion==='crear') {$q=$conexion->prepare('INSERT INTO empleados(nombre,correoElectronico,puesto,telefono,contrasena) VALUES(?,?,?,?,?)');$q->execute([$nombre,$correo,$puesto,$telefono,password_hash($clave,PASSWORD_DEFAULT)]);}
 else {
  $q=$conexion->prepare('SELECT id FROM empleados WHERE id=?');$q->execute([$id]);if(!$q->fetch()) {throw new RuntimeException('El empleado ya no existe.');}
  $sql='UPDATE empleados SET nombre=?,correoElectronico=?,puesto=?,telefono=?';$params=[$nombre,$correo,$puesto,$telefono];
  if($clave!=='') {$sql.=',contrasena=?';$params[]=password_hash($clave,PASSWORD_DEFAULT);}
  $sql.=' WHERE id=?';$params[]=$id;$conexion->prepare($sql)->execute($params);
 }
 regresarEmpleado('Empleado guardado correctamente.');
} catch(PDOException $e) {error_log('Empleados: '.$e->getCode());regresarEmpleado('No se pudo guardar. Revisa MySQL y que el correo no esté repetido.');}
catch(RuntimeException $e) {regresarEmpleado($e->getMessage());}
