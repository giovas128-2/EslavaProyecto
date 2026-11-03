<?php
require_once __DIR__ . '/../includes/adminGuard.php';
require_once __DIR__ . '/../includes/conexion.php';

function volverAlPanel(string $mensaje): void
{
    $_SESSION['admin_mensaje'] = $mensaje;
    header('Location: ../page/admin.php', true, 303);
    exit;
}

function valorAdmin(string $campo): string
{
    $valor = $_POST[$campo] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

function correoDisponible(PDO $conexion, string $correo, string $tablaActual, int $id, string $correoActual): bool
{
    $tablas = [
        'registroUsuarios' => 'correoElectronico',
        'empleados' => 'correoElectronico',
        'login' => 'correo',
        'registrarAdmins' => 'correoElectronico',
    ];
    foreach ($tablas as $tabla => $columna) {
        $sql = 'SELECT `id` FROM `' . $tabla . '` WHERE `' . $columna . '` = :correo';
        $parametros = ['correo' => $correo];
        if ($tabla === $tablaActual) {
            $sql .= ' AND `id` <> :id';
            $parametros['id'] = $id;
        } elseif ($tabla === 'login' && $tablaActual === 'registroUsuarios') {
            $sql .= ' AND `correo` <> :correoActual';
            $parametros['correoActual'] = $correoActual;
        }
        $sql .= ' LIMIT 1';
        $consulta = $conexion->prepare($sql);
        $consulta->execute($parametros);
        if ($consulta->fetch() !== false) {
            return false;
        }
    }
    return true;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
if (($_SESSION['usuario']['origen'] ?? '') !== 'registrarAdmins') {
    http_response_code(403);
    exit;
}
if (!csrfValido()) {
    volverAlPanel('Tu sesión expiró. Actualiza la página e inténtalo de nuevo.');
}

$accion = valorAdmin('accion');
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || !in_array($accion, [
    'editar_usuario', 'eliminar_usuario', 'editar_antiguo', 'eliminar_antiguo',
    'editar_admin', 'eliminar_admin',
], true)) {
    volverAlPanel('La acción solicitada no es válida.');
}

$config = require __DIR__ . '/../includes/config.php';
try {
    $conexion = conectarBaseDatos($config);
    $conexion->beginTransaction();

    if ($accion === 'eliminar_usuario') {
        $consulta = $conexion->prepare('SELECT `correoElectronico` FROM `registroUsuarios` WHERE `id` = :id');
        $consulta->execute(['id' => $id]);
        $correo = $consulta->fetchColumn();
        if ($correo === false) {
            throw new RuntimeException('No se encontró ese usuario.');
        }
        $conexion->prepare('DELETE FROM `registroUsuarios` WHERE `id` = :id')->execute(['id' => $id]);
        $conexion->prepare('DELETE FROM `login` WHERE `correo` = :correo')->execute(['correo' => $correo]);
    } elseif ($accion === 'eliminar_antiguo') {
        $consulta = $conexion->prepare('SELECT `correo` FROM `login` WHERE `id` = :id');
        $consulta->execute(['id' => $id]);
        if ($consulta->fetchColumn() === false) {
            throw new RuntimeException('No se encontró esa cuenta antigua.');
        }
        $conexion->prepare('DELETE FROM `login` WHERE `id` = :id')->execute(['id' => $id]);
    } elseif ($accion === 'eliminar_admin') {
        if ($id === (int) $_SESSION['usuario']['id']) {
            throw new RuntimeException('No puedes eliminar la cuenta de administrador que estás usando.');
        }
        $consulta = $conexion->prepare('DELETE FROM `registrarAdmins` WHERE `id` = :id');
        $consulta->execute(['id' => $id]);
        if ($consulta->rowCount() !== 1) {
            throw new RuntimeException('No se encontró ese administrador.');
        }
    } elseif ($accion === 'editar_usuario') {
        $consulta = $conexion->prepare('SELECT `correoElectronico` FROM `registroUsuarios` WHERE `id` = :id');
        $consulta->execute(['id' => $id]);
        $correoActual = $consulta->fetchColumn();
        if ($correoActual === false) {
            throw new RuntimeException('No se encontró ese usuario.');
        }
        $datos = [
            'nombre' => valorAdmin('nombre'),
            'apellidoPaterno' => valorAdmin('apellidoPaterno'),
            'apellidoMaterno' => valorAdmin('apellidoMaterno'),
            'edad' => valorAdmin('edad'),
            'genero' => valorAdmin('genero'),
            'correo' => valorAdmin('correo'),
        ];
        $edad = filter_var($datos['edad'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 120]]);
        if ($datos['nombre'] === '' || $datos['apellidoPaterno'] === '' || $datos['apellidoMaterno'] === '' || strlen($datos['nombre']) > 100 || strlen($datos['apellidoPaterno']) > 100 || strlen($datos['apellidoMaterno']) > 100) {
            throw new RuntimeException('Revisa el nombre y los apellidos; cada campo admite hasta 100 caracteres.');
        }
        if ($edad === false || !in_array($datos['genero'], ['Mujer', 'Hombre', 'No binario', 'Prefiero no decirlo'], true)) {
            throw new RuntimeException('Revisa la edad y el género seleccionados.');
        }
        if (strlen($datos['correo']) > 254 || filter_var($datos['correo'], FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Escribe un correo electrónico válido.');
        }
        if (!correoDisponible($conexion, $datos['correo'], 'registroUsuarios', $id, (string) $correoActual)) {
            throw new RuntimeException('Ese correo ya está utilizado por otra cuenta.');
        }
        $nuevaContrasena = valorAdmin('nuevaContrasena');
        if ($nuevaContrasena !== '' && (strlen($nuevaContrasena) < 8 || strlen($nuevaContrasena) > 72)) {
            throw new RuntimeException('La nueva contraseña debe tener entre 8 y 72 bytes.');
        }
        $valores = [
            'nombre' => $datos['nombre'],
            'apellidoPaterno' => $datos['apellidoPaterno'],
            'apellidoMaterno' => $datos['apellidoMaterno'],
            'edad' => $edad,
            'genero' => $datos['genero'],
            'correoElectronico' => $datos['correo'],
            'id' => $id,
        ];
        $sql = 'UPDATE `registroUsuarios` SET `nombre` = :nombre, `apellidoPaterno` = :apellidoPaterno, `apellidoMaterno` = :apellidoMaterno, `edad` = :edad, `genero` = :genero, `correoElectronico` = :correoElectronico';
        if ($nuevaContrasena !== '') {
            $hash = password_hash($nuevaContrasena, PASSWORD_DEFAULT);
            $sql .= ', `contrasena` = :contrasena';
            $valores['contrasena'] = $hash;
        }
        $sql .= ' WHERE `id` = :id';
        $conexion->prepare($sql)->execute($valores);

        $sqlLogin = 'UPDATE `login` SET `correo` = :correo';
        $valoresLogin = ['correo' => $datos['correo'], 'correoActual' => $correoActual];
        if (isset($hash)) {
            $sqlLogin .= ', `contraseña` = :contrasena';
            $valoresLogin['contrasena'] = $hash;
        }
        $sqlLogin .= ' WHERE `correo` = :correoActual';
        $conexion->prepare($sqlLogin)->execute($valoresLogin);
    } elseif ($accion === 'editar_antiguo') {
        $consulta = $conexion->prepare('SELECT `correo` FROM `login` WHERE `id` = :id');
        $consulta->execute(['id' => $id]);
        $correoActual = $consulta->fetchColumn();
        if ($correoActual === false) {
            throw new RuntimeException('No se encontró esa cuenta antigua.');
        }
        $correo = valorAdmin('correo');
        if (strlen($correo) > 254 || filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Escribe un correo electrónico válido.');
        }
        if (!correoDisponible($conexion, $correo, 'login', $id, (string) $correoActual)) {
            throw new RuntimeException('Ese correo ya está utilizado por otra cuenta.');
        }
        $nuevaContrasena = valorAdmin('nuevaContrasena');
        if ($nuevaContrasena !== '' && (strlen($nuevaContrasena) < 8 || strlen($nuevaContrasena) > 72)) {
            throw new RuntimeException('La nueva contraseña debe tener entre 8 y 72 bytes.');
        }
        $sql = 'UPDATE `login` SET `correo` = :correo';
        $valores = ['correo' => $correo, 'id' => $id];
        if ($nuevaContrasena !== '') {
            $sql .= ', `contraseña` = :contrasena';
            $valores['contrasena'] = password_hash($nuevaContrasena, PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE `id` = :id';
        $conexion->prepare($sql)->execute($valores);
    } elseif ($accion === 'editar_admin') {
        $consulta = $conexion->prepare('SELECT `correoElectronico` FROM `registrarAdmins` WHERE `id` = :id');
        $consulta->execute(['id' => $id]);
        $correoActual = $consulta->fetchColumn();
        if ($correoActual === false) {
            throw new RuntimeException('No se encontró ese administrador.');
        }
        $datos = [
            'nombre' => valorAdmin('nombre'),
            'apellidoPaterno' => valorAdmin('apellidoPaterno'),
            'apellidoMaterno' => valorAdmin('apellidoMaterno'),
            'edad' => valorAdmin('edad'),
            'genero' => valorAdmin('genero'),
            'correo' => valorAdmin('correo'),
            'puesto' => valorAdmin('puesto'),
        ];
        $edad = filter_var($datos['edad'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 120]]);
        if ($datos['nombre'] === '' || $datos['apellidoPaterno'] === '' || $datos['apellidoMaterno'] === '' || strlen($datos['nombre']) > 100 || strlen($datos['apellidoPaterno']) > 100 || strlen($datos['apellidoMaterno']) > 100 || $datos['puesto'] === '' || strlen($datos['puesto']) > 100) {
            throw new RuntimeException('Revisa el nombre, apellidos y puesto; cada campo admite hasta 100 caracteres.');
        }
        if ($edad === false || !in_array($datos['genero'], ['Mujer', 'Hombre', 'No binario', 'Prefiero no decirlo'], true)) {
            throw new RuntimeException('Revisa la edad y el género seleccionados.');
        }
        if (strlen($datos['correo']) > 254 || filter_var($datos['correo'], FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Escribe un correo electrónico válido.');
        }
        if (!correoDisponible($conexion, $datos['correo'], 'registrarAdmins', $id, (string) $correoActual)) {
            throw new RuntimeException('Ese correo ya está utilizado por otra cuenta.');
        }
        $nuevaContrasena = valorAdmin('nuevaContrasena');
        if ($nuevaContrasena !== '' && (strlen($nuevaContrasena) < 8 || strlen($nuevaContrasena) > 72)) {
            throw new RuntimeException('La nueva contraseña debe tener entre 8 y 72 bytes.');
        }
        $valores = [
            'nombre' => $datos['nombre'],
            'apellidoPaterno' => $datos['apellidoPaterno'],
            'apellidoMaterno' => $datos['apellidoMaterno'],
            'edad' => $edad,
            'genero' => $datos['genero'],
            'correoElectronico' => $datos['correo'],
            'puesto' => $datos['puesto'],
            'id' => $id,
        ];
        $sql = 'UPDATE `registrarAdmins` SET `nombre` = :nombre, `apellidoPaterno` = :apellidoPaterno, `apellidoMaterno` = :apellidoMaterno, `edad` = :edad, `genero` = :genero, `correoElectronico` = :correoElectronico, `puesto` = :puesto';
        if ($nuevaContrasena !== '') {
            $sql .= ', `contrasena` = :contrasena';
            $valores['contrasena'] = password_hash($nuevaContrasena, PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE `id` = :id';
        $conexion->prepare($sql)->execute($valores);
        if ($id === (int) $_SESSION['usuario']['id']) {
            $_SESSION['usuario']['nombre'] = $datos['nombre'];
            $_SESSION['usuario']['puesto'] = $datos['puesto'];
        }
    }

    $conexion->commit();
    $mensajes = [
        'editar_usuario' => 'Datos del usuario actualizados.',
        'eliminar_usuario' => 'Usuario eliminado.',
        'editar_antiguo' => 'Cuenta antigua actualizada.',
        'eliminar_antiguo' => 'Cuenta antigua eliminada.',
        'editar_admin' => 'Datos del administrador actualizados.',
        'eliminar_admin' => 'Administrador eliminado.',
    ];
    volverAlPanel($mensajes[$accion]);
} catch (PDOException $e) {
    if (isset($conexion) && $conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log('Eslava: no se pudo gestionar una cuenta. Código: ' . $e->getCode());
    volverAlPanel($e->getCode() === '23000' ? 'Ese correo ya está utilizado por otra cuenta.' : 'No se pudo completar el cambio. Revisa la conexión y el esquema de la base de datos.');
} catch (RuntimeException $e) {
    if (isset($conexion) && $conexion->inTransaction()) {
        $conexion->rollBack();
    }
    volverAlPanel($e->getMessage());
}
