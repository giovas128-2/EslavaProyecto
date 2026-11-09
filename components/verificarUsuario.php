<?php
require_once __DIR__ . '/../includes/sesion.php';
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/bitacora.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../page/login.php');
    exit;
}
function volverConError(string $mensaje): void
{
    global $conexion, $correo, $usuarioIntentado, $falloRegistrado;
    if (isset($conexion) && !$falloRegistrado) {
        try { registrarEvento($conexion, $correo ?? '', 'entrada', 'fallido', $usuarioIntentado ?? null); $falloRegistrado = true; }
        catch (PDOException $e) { error_log('No se pudo registrar el intento de acceso.'); }
    }
    $_SESSION['error'] = $mensaje;
    header('Location: ../page/login.php', true, 303);
    exit;
}
$falloRegistrado = false;
$correo = isset($_POST['correo']) && is_string($_POST['correo']) ? trim($_POST['correo']) : '';
try { $conexion = conectarBaseDatos(require __DIR__ . '/../includes/config.php'); }
catch (PDOException $e) { volverConError('No se pudo conectar con MySQL.'); }
if (!csrfValido()) {
    volverConError('Tu sesión expiró. Intenta iniciar sesión de nuevo.');
}
$correo = isset($_POST['correo']) && is_string($_POST['correo']) ? trim($_POST['correo']) : '';
$contrasena = isset($_POST['contrasena']) && is_string($_POST['contrasena']) ? $_POST['contrasena'] : '';
$_SESSION['correo_intentado'] = substr($correo, 0, 254);
if ($correo === '' || $contrasena === '') {
    volverConError('Escribe tu correo electrónico y tu contraseña.');
}
if (strlen($correo) > 254 || strlen($contrasena) > 4096) {
    volverConError('Los datos ingresados son demasiado largos.');
}
$config = require __DIR__ . '/../includes/config.php';
try {
    $conexion = conectarBaseDatos($config);
    $usuarioValido = null;
    $origen = '';
    $puesto = null;

    $consultaAdmin = $conexion->prepare('SELECT `id`, `nombre`, `puesto`, `contrasena` FROM `registrarAdmins` WHERE `correoElectronico` = :correoElectronico LIMIT 1');
    $consultaAdmin->execute(['correoElectronico' => $correo]);
    $usuarioAdmin = $consultaAdmin->fetch();
    if ($usuarioAdmin !== false) {
        $usuarioIntentado = ['id'=>$usuarioAdmin['id'],'origen'=>'registrarAdmins'];
        if (!password_verify($contrasena, (string) $usuarioAdmin['contrasena'])) {
            volverConError('Usuario no encontrado o contraseña incorrecta.');
        }
        $usuarioValido = $usuarioAdmin;
        $origen = 'registrarAdmins';
        $puesto = $usuarioAdmin['puesto'];
    } else {
        $consultaRegistro = $conexion->prepare('SELECT `id`, `nombre`, `contrasena` FROM `registroUsuarios` WHERE `correoElectronico` = :correoElectronico LIMIT 1');
        $consultaRegistro->execute(['correoElectronico' => $correo]);
        $usuarioRegistro = $consultaRegistro->fetch();
        if ($usuarioRegistro !== false) {
            $usuarioIntentado = ['id'=>$usuarioRegistro['id'],'origen'=>'registroUsuarios'];
            if (password_verify($contrasena, (string) $usuarioRegistro['contrasena'])) {
                $usuarioValido = $usuarioRegistro;
                $origen = 'registroUsuarios';
            }
        } else {
            $qEmpleado = $conexion->prepare('SELECT id,nombre,contrasena,puesto FROM empleados WHERE correoElectronico = ?');
            $qEmpleado->execute([$correo]);
            $empleado = $qEmpleado->fetch();
            if ($empleado) {
                $usuarioIntentado = ['id'=>$empleado['id'],'origen'=>'empleados'];
                if (!password_verify($contrasena, $empleado['contrasena'])) { volverConError('Usuario no encontrado o contraseña incorrecta.'); }
                $usuarioValido = $empleado; $origen = 'empleados'; $puesto = $empleado['puesto'];
            } else {
            // Se conserva el acceso de las cuentas antiguas guardadas en login.
            $consulta = $conexion->prepare('SELECT `id`, `correo` AS `nombre`, `contraseña` FROM `login` WHERE `correo` = :correo');
            $consulta->execute(['correo' => $correo]);
            while ($usuario = $consulta->fetch()) {
                $usuarioIntentado = ['id'=>$usuario['id'],'origen'=>'login'];
                $guardada = (string) $usuario['contraseña'];
                $info = password_get_info($guardada);
                $esHash = ($info['algoName'] ?? 'unknown') !== 'unknown';
                $coincide = $esHash
                    ? password_verify($contrasena, $guardada)
                    : ($config['permitir_password_texto'] && hash_equals($guardada, $contrasena));
                if ($coincide) {
                    $usuarioValido = $usuario;
                    $origen = 'login';
                    break;
                }
            }
        }
    }
    }
    if ($usuarioValido === null) {
        volverConError('Usuario no encontrado o contraseña incorrecta.');
    }
    registrarEvento($conexion, $correo, 'entrada', 'exitoso', ['id'=>$usuarioValido['id'],'origen'=>$origen]);
    $falloRegistrado = true;
    session_regenerate_id(true);
    $_SESSION['usuario'] = ['id' => $usuarioValido['id'], 'nombre' => $usuarioValido['nombre'], 'origen' => $origen, 'correo' => $correo];
    if ($puesto !== null) {
        $_SESSION['usuario']['puesto'] = $puesto;
    }
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    unset($_SESSION['error'], $_SESSION['correo_intentado']);
    $next=$_SESSION['despues_login']??'';unset($_SESSION['despues_login']);
    $retornoQr = is_string($next) && preg_match('/^productoQr\.php\?(?:detalle=[1-9][0-9]{0,17}|carrito=[a-f0-9]{64})$/D', $next);
    $destino = (in_array($next,['checkout.php','misPedidos.php'],true) || $retornoQr) ? '../page/'.$next : ($origen === 'registrarAdmins' ? '../page/admin.php' : '../page/tienda.php');
    header('Location: ' . $destino, true, 303);
    exit;
} catch (PDOException $e) {
    error_log('Eslava: no se pudo consultar el login. Código: ' . $e->getCode());
    volverConError('No se pudo conectar o consultar la base de datos. Revisa MySQL y el archivo includes/config.php.');
}
