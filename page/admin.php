<?php
require_once __DIR__ . '/../includes/adminGuard.php';
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
if (($_SESSION['usuario']['origen'] ?? '') !== 'registrarAdmins') {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';
$mensajePanel = $_SESSION['admin_mensaje'] ?? '';
unset($_SESSION['admin_mensaje']);
$usuariosRegistrados = [];
$usuariosAntiguos = [];
$administradores = [];
$errorCarga = '';
$config = require __DIR__ . '/../includes/config.php';
try {
    $conexion = conectarBaseDatos($config);
    $consulta = $conexion->prepare('UPDATE `registrarAdmins` SET `ultimoAcceso` = CURRENT_TIMESTAMP WHERE `id` = :id');
    $consulta->execute(['id' => (int) $_SESSION['usuario']['id']]);
    $usuariosRegistrados = $conexion->query('SELECT `id`, `nombre`, `apellidoPaterno`, `apellidoMaterno`, `edad`, `genero`, `correoElectronico`, `fechaRegistro`, `ultimoAcceso` FROM `registroUsuarios` ORDER BY `apellidoPaterno`, `nombre`')->fetchAll();
    $usuariosAntiguos = $conexion->query('SELECT `login`.`id`, `login`.`correo` FROM `login` WHERE `login`.`correo` IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `registroUsuarios` WHERE `registroUsuarios`.`correoElectronico` COLLATE utf8mb4_unicode_ci = `login`.`correo` COLLATE utf8mb4_unicode_ci) ORDER BY `login`.`correo`')->fetchAll();
    $administradores = $conexion->query('SELECT `id`, `nombre`, `apellidoPaterno`, `apellidoMaterno`, `edad`, `genero`, `correoElectronico`, `puesto`, `fechaRegistro`, `ultimoAcceso` FROM `registrarAdmins` ORDER BY `apellidoPaterno`, `nombre`')->fetchAll();
} catch (PDOException $e) {
    error_log('Eslava: no se pudo cargar el panel de administración. Código: ' . $e->getCode());
    $errorCarga = 'No se pudieron cargar las cuentas. Revisa la conexión con la base de datos.';
}
$generos = ['Mujer', 'Hombre', 'No binario', 'Prefiero no decirlo'];
$campo = static function (string $nombre, string $etiqueta, string $valor, string $tipo = 'text', string $atributos = ''): string {
    return '<label class="admin-field"><span>' . escapar($etiqueta) . '</span><input name="' . escapar($nombre) . '" type="' . escapar($tipo) . '" value="' . escapar($valor) . '" ' . $atributos . '></label>';
};
$campoGenero = static function (string $valor) use ($generos): string {
    $html = '<label class="admin-field"><span>Género</span><select name="genero" required><option value="">Selecciona</option>';
    foreach ($generos as $genero) {
        $seleccionado = $valor === $genero ? ' selected' : '';
        $html .= '<option value="' . escapar($genero) . '"' . $seleccionado . '>' . escapar($genero) . '</option>';
    }
    return $html . '</select></label>';
};
$formularioBase = static function (string $accion, int $id): string {
    return '<input type="hidden" name="csrf" value="' . escapar($_SESSION['csrf']) . '"><input type="hidden" name="accion" value="' . escapar($accion) . '"><input type="hidden" name="id" value="' . $id . '">';
};
$fecha = static function ($valor): string {
    if ($valor === null || $valor === '') {
        return 'Sin registro';
    }
    $instante = strtotime((string) $valor);
    return $instante === false ? (string) $valor : date('d/m/Y H:i', $instante);
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios y empleados | Guitarixta</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="admin.php" aria-label="Guitarixta, administración">GUITARIXTA<span class="brand-dot">.</span></a>
        <div class="admin-header-actions">
            <span class="header-label">ADMINISTRACIÓN <span class="status-dot"></span></span>
            <form action="../components/cerrarSesion.php" method="post">
                <input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>">
                <button class="admin-logout" type="submit" aria-label="Cerrar sesión" title="Cerrar sesión">↗</button>
            </form>
        </div>
    </header>
    <?php require __DIR__ . '/../includes/adminMenu.php'; ?>
    <main class="admin-content">
        <div class="admin-shell">
            <section class="admin-intro" aria-labelledby="titulo">
                <div>
                    <span class="eyebrow">TIENDA / CONTROL DE ACCESO</span>
                    <h1 id="titulo">Usuarios y empleados</h1>
                    <p>Hola, <?= escapar((string) $_SESSION['usuario']['nombre']) ?> · <?= escapar((string) $_SESSION['usuario']['puesto']) ?></p>
                </div>
                <nav class="admin-nav" aria-label="Secciones de cuentas">
                    <a href="#usuarios">Usuarios <span><?= count($usuariosRegistrados) + count($usuariosAntiguos) ?></span></a>
                    <a href="empleados.php">Empleados</a>
                    <a href="#administradores">Administradores <span><?= count($administradores) ?></span></a>
                </nav>
            </section>

            <?php if ($mensajePanel !== ''): ?>
                <div class="admin-notice" role="status"><?= escapar($mensajePanel) ?></div>
            <?php endif; ?>
            <?php if ($errorCarga !== ''): ?>
                <div class="alert" role="alert"><?= escapar($errorCarga) ?></div>
            <?php endif; ?>

            <section class="panel-card"><h2>Configuración de empleados</h2><p class="panel-note">Administra los datos del personal, su puesto y sus credenciales de acceso.</p><a class="admin-add-user" href="empleados.php">Ver y configurar empleados →</a></section>
            <section class="admin-section" id="usuarios" aria-labelledby="usuarios-titulo">
                <div class="admin-section-heading">
                    <div><span class="eyebrow">CUENTAS DE CLIENTES Y PERSONAL</span><h2 id="usuarios-titulo">Usuarios</h2></div>
                    <div class="admin-section-tools">
                        <span class="admin-count"><?= count($usuariosRegistrados) + count($usuariosAntiguos) ?> registros</span>
                        <a class="admin-add-user" href="graficas.php">▥ Gráficas</a>
                        <a class="admin-add-user" href="register.php?origen=admin"><span aria-hidden="true">+</span> Agregar usuario</a>
                    </div>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Nombre</th><th>Edad / género</th><th>Correo</th><th>Registro</th><th>Último acceso</th><th>Editar</th><th>Eliminar</th></tr></thead>
                        <tbody>
                            <?php foreach ($usuariosRegistrados as $usuario): ?>
                                <?php $nombreCompleto = trim($usuario['nombre'] . ' ' . $usuario['apellidoPaterno'] . ' ' . $usuario['apellidoMaterno']); ?>
                                <tr>
                                    <td data-label="Nombre"><strong><?= escapar($nombreCompleto) ?></strong><small>Perfil completo</small></td>
                                    <td data-label="Edad / género"><?= escapar((string) $usuario['edad']) ?> años<small><?= escapar($usuario['genero']) ?></small></td>
                                    <td data-label="Correo"><?= escapar($usuario['correoElectronico']) ?></td>
                                    <td data-label="Registro"><?= escapar($fecha($usuario['fechaRegistro'])) ?></td>
                                    <td data-label="Último acceso"><?= escapar($fecha($usuario['ultimoAcceso'])) ?></td>
                                    <td data-label="Editar">
                                        <button class="admin-edit-trigger" type="button" onclick="this.nextElementSibling.showModal()">Editar</button>
                                        <dialog class="admin-dialog" aria-labelledby="titulo-usuario-<?= (int) $usuario['id'] ?>">
                                            <form action="../components/gestionarUsuarios.php" method="post">
                                                <div class="admin-dialog-heading">
                                                    <div><span class="eyebrow">PERFIL DE USUARIO</span><h3 id="titulo-usuario-<?= (int) $usuario['id'] ?>">Editar cuenta</h3></div>
                                                    <button class="admin-dialog-close" type="button" onclick="this.closest('dialog').close()" aria-label="Cerrar edición">×</button>
                                                </div>
                                                <?= $formularioBase('editar_usuario', (int) $usuario['id']) ?>
                                                <div class="admin-edit-grid">
                                                    <?= $campo('nombre', 'Nombre', $usuario['nombre'], 'text', 'maxlength="100" required') ?>
                                                    <?= $campo('apellidoPaterno', 'Apellido paterno', $usuario['apellidoPaterno'], 'text', 'maxlength="100" required') ?>
                                                    <?= $campo('apellidoMaterno', 'Apellido materno', $usuario['apellidoMaterno'], 'text', 'maxlength="100" required') ?>
                                                    <?= $campo('edad', 'Edad', (string) $usuario['edad'], 'number', 'min="1" max="120" required') ?>
                                                    <?= $campoGenero($usuario['genero']) ?>
                                                    <?= $campo('correo', 'Correo electrónico', $usuario['correoElectronico'], 'email', 'maxlength="254" required') ?>
                                                    <?= $campo('nuevaContrasena', 'Nueva contraseña (opcional)', '', 'password', 'minlength="8" maxlength="72" autocomplete="new-password"') ?>
                                                </div>
                                                <div class="admin-dialog-actions">
                                                    <button class="admin-cancel" type="button" onclick="this.closest('dialog').close()">Cancelar</button>
                                                    <button class="admin-save" type="submit">Guardar cambios</button>
                                                </div>
                                            </form>
                                        </dialog>
                                    </td>
                                    <td data-label="Eliminar">
                                        <form action="../components/gestionarUsuarios.php" method="post" onsubmit="return confirm('¿Eliminar este usuario y su acceso?')">
                                            <?= $formularioBase('eliminar_usuario', (int) $usuario['id']) ?>
                                            <button class="admin-delete" type="submit">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php foreach ($usuariosAntiguos as $usuario): ?>
                                <tr>
                                    <td data-label="Nombre"><strong>Cuenta anterior</strong><small>Sin perfil adicional</small></td>
                                    <td data-label="Edad / género">No disponible</td>
                                    <td data-label="Correo"><?= escapar((string) $usuario['correo']) ?></td>
                                    <td data-label="Registro">No disponible</td>
                                    <td data-label="Último acceso">No disponible</td>
                                    <td data-label="Editar">
                                        <button class="admin-edit-trigger" type="button" onclick="this.nextElementSibling.showModal()">Editar</button>
                                        <dialog class="admin-dialog" aria-labelledby="titulo-antiguo-<?= (int) $usuario['id'] ?>">
                                            <form action="../components/gestionarUsuarios.php" method="post">
                                                <div class="admin-dialog-heading">
                                                    <div><span class="eyebrow">CUENTA ANTERIOR</span><h3 id="titulo-antiguo-<?= (int) $usuario['id'] ?>">Editar acceso</h3></div>
                                                    <button class="admin-dialog-close" type="button" onclick="this.closest('dialog').close()" aria-label="Cerrar edición">×</button>
                                                </div>
                                                <?= $formularioBase('editar_antiguo', (int) $usuario['id']) ?>
                                                <div class="admin-edit-grid">
                                                    <?= $campo('correo', 'Correo electrónico', (string) $usuario['correo'], 'email', 'maxlength="254" required') ?>
                                                    <?= $campo('nuevaContrasena', 'Nueva contraseña (opcional)', '', 'password', 'minlength="8" maxlength="72" autocomplete="new-password"') ?>
                                                </div>
                                                <div class="admin-dialog-actions">
                                                    <button class="admin-cancel" type="button" onclick="this.closest('dialog').close()">Cancelar</button>
                                                    <button class="admin-save" type="submit">Guardar cambios</button>
                                                </div>
                                            </form>
                                        </dialog>
                                    </td>
                                    <td data-label="Eliminar">
                                        <form action="../components/gestionarUsuarios.php" method="post" onsubmit="return confirm('¿Eliminar esta cuenta anterior?')">
                                            <?= $formularioBase('eliminar_antiguo', (int) $usuario['id']) ?>
                                            <button class="admin-delete" type="submit">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($usuariosRegistrados === [] && $usuariosAntiguos === []): ?>
                                <tr><td colspan="7" class="admin-empty">No hay usuarios registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="admin-section" id="administradores" aria-labelledby="admins-titulo">
                <div class="admin-section-heading">
                    <div><span class="eyebrow">EQUIPO DE ADMINISTRACIÓN</span><h2 id="admins-titulo">Administradores</h2></div>
                    <span class="admin-count"><?= count($administradores) ?> registros</span>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Nombre</th><th>Puesto</th><th>Correo</th><th>Registro</th><th>Último acceso</th><th>Editar</th><th>Eliminar</th></tr></thead>
                        <tbody>
                            <?php foreach ($administradores as $admin): ?>
                                <?php $nombreCompleto = trim($admin['nombre'] . ' ' . $admin['apellidoPaterno'] . ' ' . $admin['apellidoMaterno']); ?>
                                <?php $esActual = (int) $admin['id'] === (int) $_SESSION['usuario']['id']; ?>
                                <tr>
                                    <td data-label="Nombre"><strong><?= escapar($nombreCompleto) ?></strong><small><?= escapar((string) $admin['edad']) ?> años · <?= escapar($admin['genero']) ?><?= $esActual ? ' · Tú' : '' ?></small></td>
                                    <td data-label="Puesto"><?= escapar($admin['puesto']) ?></td>
                                    <td data-label="Correo"><?= escapar($admin['correoElectronico']) ?></td>
                                    <td data-label="Registro"><?= escapar($fecha($admin['fechaRegistro'])) ?></td>
                                    <td data-label="Último acceso"><?= escapar($fecha($admin['ultimoAcceso'])) ?></td>
                                    <td data-label="Editar">
                                        <button class="admin-edit-trigger" type="button" onclick="this.nextElementSibling.showModal()">Editar</button>
                                        <dialog class="admin-dialog" aria-labelledby="titulo-admin-<?= (int) $admin['id'] ?>">
                                            <form action="../components/gestionarUsuarios.php" method="post">
                                                <div class="admin-dialog-heading">
                                                    <div><span class="eyebrow">EQUIPO DE ADMINISTRACIÓN</span><h3 id="titulo-admin-<?= (int) $admin['id'] ?>">Editar administrador</h3></div>
                                                    <button class="admin-dialog-close" type="button" onclick="this.closest('dialog').close()" aria-label="Cerrar edición">×</button>
                                                </div>
                                                <?= $formularioBase('editar_admin', (int) $admin['id']) ?>
                                                <div class="admin-edit-grid">
                                                    <?= $campo('nombre', 'Nombre', $admin['nombre'], 'text', 'maxlength="100" required') ?>
                                                    <?= $campo('apellidoPaterno', 'Apellido paterno', $admin['apellidoPaterno'], 'text', 'maxlength="100" required') ?>
                                                    <?= $campo('apellidoMaterno', 'Apellido materno', $admin['apellidoMaterno'], 'text', 'maxlength="100" required') ?>
                                                    <?= $campo('edad', 'Edad', (string) $admin['edad'], 'number', 'min="1" max="120" required') ?>
                                                    <?= $campoGenero($admin['genero']) ?>
                                                    <?= $campo('correo', 'Correo electrónico', $admin['correoElectronico'], 'email', 'maxlength="254" required') ?>
                                                    <?= $campo('puesto', 'Puesto', $admin['puesto'], 'text', 'maxlength="100" required') ?>
                                                    <?= $campo('nuevaContrasena', 'Nueva contraseña (opcional)', '', 'password', 'minlength="8" maxlength="72" autocomplete="new-password"') ?>
                                                </div>
                                                <div class="admin-dialog-actions">
                                                    <button class="admin-cancel" type="button" onclick="this.closest('dialog').close()">Cancelar</button>
                                                    <button class="admin-save" type="submit">Guardar cambios</button>
                                                </div>
                                            </form>
                                        </dialog>
                                    </td>
                                    <td data-label="Eliminar">
                                        <?php if ($esActual): ?>
                                            <span class="admin-self-note">Tu cuenta</span>
                                        <?php else: ?>
                                            <form action="../components/gestionarUsuarios.php" method="post" onsubmit="return confirm('¿Eliminar este administrador?')">
                                                <?= $formularioBase('eliminar_admin', (int) $admin['id']) ?>
                                                <button class="admin-delete" type="submit">Eliminar</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($administradores === []): ?>
                                <tr><td colspan="7" class="admin-empty">No hay administradores registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
    <footer class="site-footer"><span>GUITARIXTA</span><span>Administración de tienda</span><span class="footer-number">ADMIN / CUENTAS</span></footer>
</body>
</html>