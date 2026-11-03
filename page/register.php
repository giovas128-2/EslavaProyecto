<?php
require_once __DIR__ . '/../includes/sesion.php';
$registroAdminSolicitado = ($_GET['origen'] ?? '') === 'admin';
$esAdmin = (($_SESSION['usuario']['origen'] ?? '') === 'registrarAdmins');
if ($registroAdminSolicitado && !$esAdmin) {
	header('Location: login.php');
	exit;
}
$modoAdmin = $registroAdminSolicitado && $esAdmin;
if (isset($_SESSION['usuario']) && !$modoAdmin) {
	header('Location: index.php');
	exit;
}
$error = $_SESSION['error_registro'] ?? '';
$datos = $_SESSION['registro_intentado'] ?? [];
unset($_SESSION['error_registro'], $_SESSION['registro_intentado']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= $modoAdmin ? 'Agregar usuario' : 'Crear cuenta' ?> | Guitarixta</title>
	<link rel="stylesheet" href="../assets/css/login.css">
	<script src="../assets/js/login.js" defer></script>
</head>
<body>
	<header class="site-header">
		<a class="brand" href="login.php" aria-label="Eslava, inicio">GUITARIXTA<span class="brand-dot">.</span></a>
		<span class="header-label">GUITARIXTA <span class="small-line"></span> <?= $modoAdmin ? 'ALTA DE USUARIO' : 'REGISTRO' ?></span>
	</header>
	<main class="main-content">
		<section class="register-panel" aria-labelledby="titulo">
			<span class="eyebrow"><?= $modoAdmin ? 'PERSONAL DE LA TIENDA' : 'COMIENZA TU CAMINO' ?></span>
			<h1 id="titulo"><?= $modoAdmin ? 'Agregar usuario.' : 'Crear cuenta.' ?></h1>
			<p class="intro"><?= $modoAdmin ? 'Ingresa los datos que recibirá el empleado.' : 'Completa tus datos para registrarte.' ?></p>
			<form action="../components/insertUser.php" method="post" class="register-form">
				<input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>">
				<?php if ($modoAdmin): ?>
					<input type="hidden" name="origen" value="admin">
				<?php endif; ?>
				<?php if ($error !== ''): ?>
					<div class="alert" role="alert"><?= escapar($error) ?></div>
				<?php endif; ?>
				<div class="register-grid">
					<div class="field-group">
						<label for="nombre">Nombre</label>
						<input id="nombre" name="nombre" type="text" placeholder="Tu nombre" value="<?= escapar((string) ($datos['nombre'] ?? '')) ?>" autocomplete="given-name" maxlength="100" required>
					</div>
					<div class="field-group">
						<label for="apellidoPaterno">Apellido paterno</label>
						<input id="apellidoPaterno" name="apellidoPaterno" type="text" placeholder="Apellido paterno" value="<?= escapar((string) ($datos['apellidoPaterno'] ?? '')) ?>" autocomplete="family-name" maxlength="100" required>
					</div>
					<div class="field-group">
						<label for="apellidoMaterno">Apellido materno</label>
						<input id="apellidoMaterno" name="apellidoMaterno" type="text" placeholder="Apellido materno" value="<?= escapar((string) ($datos['apellidoMaterno'] ?? '')) ?>" autocomplete="additional-name" maxlength="100" required>
					</div>
					<div class="field-group">
						<label for="edad">Edad</label>
						<input id="edad" name="edad" type="number" min="1" max="120" placeholder="Tu edad" value="<?= escapar((string) ($datos['edad'] ?? '')) ?>" required>
					</div>
					<div class="field-group field-wide">
						<label for="genero">Género</label>
						<select id="genero" name="genero" required>
							<option value="">Selecciona una opción</option>
							<?php foreach (['Mujer', 'Hombre', 'No binario', 'Prefiero no decirlo'] as $opcion): ?>
								<option value="<?= escapar($opcion) ?>" <?= ($datos['genero'] ?? '') === $opcion ? 'selected' : '' ?>><?= escapar($opcion) ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="field-group field-wide">
						<label for="correoElectronico">Correo electrónico</label>
						<input id="correoElectronico" name="correoElectronico" type="email" placeholder="tu@correo.com" value="<?= escapar((string) ($datos['correoElectronico'] ?? '')) ?>" autocomplete="email" maxlength="254" required>
					</div>
					<div class="field-group">
						<label for="contrasena">Contraseña</label>
						<div class="password-field">
							<input id="contrasena" name="contrasena" type="password" placeholder="Crea una contraseña" autocomplete="new-password" maxlength="72" required>
							<button class="password-toggle" type="button" aria-label="Mostrar contraseña" aria-controls="contrasena" aria-pressed="false" hidden>
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
							</button>
						</div>
					</div>
					<div class="field-group">
						<label for="confirmarContrasena">Verificar contraseña</label>
						<input id="confirmarContrasena" name="confirmarContrasena" type="password" placeholder="Repite la contraseña" autocomplete="new-password" maxlength="72" required>
					</div>
					<button class="submit-button field-wide" type="submit"><?= $modoAdmin ? 'Registrar usuario' : 'Crear cuenta' ?> <span aria-hidden="true">→</span></button>
				</div>
			</form>
			<?php if ($modoAdmin): ?>
				<p class="register-back"><a href="admin.php">Volver al panel</a></p>
			<?php else: ?>
				<p class="register-back">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
			<?php endif; ?>
		</section>
	</main>
	<footer class="site-footer"><span>GUITARIXTA</span><span>Un nuevo camino comienza aquí.</span><span class="footer-number">01 / REGISTRO</span></footer>
</body>
</html>
