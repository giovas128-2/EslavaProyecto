<?php
require_once __DIR__ . '/../includes/sesion.php';
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/bitacora.php';

function volverARegistro(string $mensaje, array $datos = []): void
{
    $_SESSION['error_registro'] = $mensaje;
    $_SESSION['registro_intentado'] = $datos;
    $esRegistroAdmin = (($_SESSION['usuario']['origen'] ?? '') === 'registrarAdmins') && (($_POST['origen'] ?? '') === 'admin');
    $destino = $esRegistroAdmin ? '../page/register.php?origen=admin' : '../page/register.php';
    header('Location: ' . $destino, true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $destino = (($_SESSION['usuario']['origen'] ?? '') === 'registrarAdmins') ? '../page/register.php?origen=admin' : '../page/register.php';
    header('Location: ' . $destino);
    exit;
}

if (!csrfValido()) {
    volverARegistro('Tu sesión expiró. Vuelve a intentarlo.');
}
$sesionAdmin = (($_SESSION['usuario']['origen'] ?? '') === 'registrarAdmins');
$registroDesdeAdmin = $sesionAdmin && (($_POST['origen'] ?? '') === 'admin');
if ($sesionAdmin && !$registroDesdeAdmin) {
    header('Location: ../page/admin.php', true, 303);
    exit;
}

$campos = ['nombre', 'apellidoPaterno', 'apellidoMaterno', 'edad', 'genero', 'correoElectronico'];
$datos = [];
foreach ($campos as $campo) {
    $valor = $_POST[$campo] ?? '';
    $datos[$campo] = is_string($valor) ? trim($valor) : '';
}
$contrasena = isset($_POST['contrasena']) && is_string($_POST['contrasena']) ? $_POST['contrasena'] : '';
$confirmarContrasena = isset($_POST['confirmarContrasena']) && is_string($_POST['confirmarContrasena']) ? $_POST['confirmarContrasena'] : '';

foreach (['nombre', 'apellidoPaterno', 'apellidoMaterno', 'edad', 'genero', 'correoElectronico'] as $campo) {
    if ($datos[$campo] === '') {
        volverARegistro('Completa todos los campos.', $datos);
    }
}
if (strlen($datos['nombre']) > 100 || strlen($datos['apellidoPaterno']) > 100 || strlen($datos['apellidoMaterno']) > 100) {
    volverARegistro('Los nombres y apellidos no pueden superar 100 caracteres.', $datos);
}
$edad = filter_var($datos['edad'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 120]]);
if ($edad === false) {
    volverARegistro('La edad debe ser un número entre 1 y 120.', $datos);
}
if (!in_array($datos['genero'], ['Mujer', 'Hombre', 'No binario', 'Prefiero no decirlo'], true)) {
    volverARegistro('Selecciona una opción válida para el género.', $datos);
}
if (strlen($datos['correoElectronico']) > 254 || filter_var($datos['correoElectronico'], FILTER_VALIDATE_EMAIL) === false) {
    volverARegistro('Escribe un correo electrónico válido.', $datos);
}
if (strlen($contrasena) < 8 || strlen($contrasena) > 72) {
    volverARegistro('La contraseña debe tener entre 8 y 72 bytes.', $datos);
}
if (!hash_equals($contrasena, $confirmarContrasena)) {
    volverARegistro('Las contraseñas no coinciden.', $datos);
}

$config = require __DIR__ . '/../includes/config.php';
try {
    $conexion = conectarBaseDatos($config);
    foreach (['registrarAdmins', 'empleados'] as $tabla) {
        $q = $conexion->prepare('SELECT id FROM `'.$tabla.'` WHERE correoElectronico=?');
        $q->execute([$datos['correoElectronico']]);
        if ($q->fetch()) { volverARegistro('Ese correo ya pertenece a otra cuenta.', $datos); }
    }
    $hashContrasena = password_hash($contrasena, PASSWORD_DEFAULT);
    $conexion->beginTransaction();

    $consulta = $conexion->prepare(
        'INSERT INTO `registroUsuarios` (`nombre`, `apellidoPaterno`, `apellidoMaterno`, `edad`, `genero`, `correoElectronico`, `contrasena`) VALUES (:nombre, :apellidoPaterno, :apellidoMaterno, :edad, :genero, :correoElectronico, :contrasena)'
    );
    $consulta->execute([
        'nombre' => $datos['nombre'],
        'apellidoPaterno' => $datos['apellidoPaterno'],
        'apellidoMaterno' => $datos['apellidoMaterno'],
        'edad' => $edad,
        'genero' => $datos['genero'],
        'correoElectronico' => $datos['correoElectronico'],
        'contrasena' => $hashContrasena,
    ]);
    $idRegistro = (int) $conexion->lastInsertId();

    $consultaLogin = $conexion->prepare(
        'INSERT INTO `login` (`correo`, `contraseña`) VALUES (:correo, :contrasena)'
    );
    $consultaLogin->execute([
        'correo' => $datos['correoElectronico'],
        'contrasena' => $hashContrasena,
    ]);
    if (!$registroDesdeAdmin) {
        registrarEvento($conexion, $datos['correoElectronico'], 'entrada', 'exitoso', ['id'=>$idRegistro,'origen'=>'registroUsuarios']);
    }
    $conexion->commit();

    if ($registroDesdeAdmin) {
        unset($_SESSION['error_registro'], $_SESSION['registro_intentado']);
        $_SESSION['admin_mensaje'] = 'Usuario registrado. Comparte sus credenciales de acceso con el empleado.';
        header('Location: ../page/admin.php', true, 303);
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    $_SESSION['usuario'] = [
        'id' => $idRegistro,
        'nombre' => $datos['nombre'],
        'origen' => 'registroUsuarios',
        'correo' => $datos['correoElectronico'],
    ];
    unset($_SESSION['error_registro'], $_SESSION['registro_intentado']);
    $next=$_SESSION['despues_login']??'';unset($_SESSION['despues_login']);
    header('Location: ../page/'.(in_array($next,['checkout.php','misPedidos.php'],true)?$next:'tienda.php'), true, 303);
    exit;
} catch (PDOException $e) {
    if (isset($conexion) && $conexion->inTransaction()) {
        $conexion->rollBack();
    }
    if ($e->getCode() === '23000') {
        volverARegistro('Ese correo electrónico ya está registrado en la tabla de usuarios.', $datos);
    }
    error_log('Eslava: no se pudo registrar al usuario. Código: ' . $e->getCode());
    volverARegistro('No se pudo guardar el registro. Revisa MySQL y que exista la tabla registroUsuarios.', $datos);
}