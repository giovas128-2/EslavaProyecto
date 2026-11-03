<?php
require_once __DIR__ . '/../includes/sesion.php';
if (isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}
$error = $_SESSION['error'] ?? '';
$correo = $_SESSION['correo_intentado'] ?? '';
unset($_SESSION['error'], $_SESSION['correo_intentado']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | Guitarixta</title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <script src="../assets/js/login.js" defer></script>
</head>
<body>
<?php if (isset($_GET['salida_sin_registro'])): ?><p class="alert" role="alert">La sesión se cerró, pero no se pudo guardar la salida en la bitácora. Revisa MySQL.</p><?php endif; ?>
    <header class="site-header">
        <a class="brand" href="tienda.php" aria-label="Eslava, inicio">GUITARIXTA<span class="brand-dot">.</span></a>
        <span class="header-label">GUITARIXTA <span class="small-line"></span> ACCESO</span>
    </header>
    <main class="main-content">
        <section class="login-panel" aria-labelledby="titulo">
            <span class="eyebrow">TU ESPACIO, DE NUEVO</span>
            <h1 id="titulo">Bienvenido.</h1>
            <p class="intro">Tu próxima guitarra te espera.</p>
            <form action="../components/verificarUsuario.php" method="post" class="login-form">
                <input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>">
                <?php if ($error !== ''): ?>
                    <div class="alert" role="alert"><?= escapar($error) ?></div>
                <?php endif; ?>
                <div class="field-group">
                    <label for="correo">Correo electrónico</label>
                    <input id="correo" name="correo" type="email" placeholder="tu@correo.com" value="<?= escapar($correo) ?>" autocomplete="email" maxlength="254" required>
                </div>
                <div class="field-group">
                    <label for="contrasena">Contraseña</label>
                    <div class="password-field">
                        <input id="contrasena" name="contrasena" type="password" placeholder="Escribe tu contraseña" autocomplete="current-password" maxlength="4096" required>
                        <button class="password-toggle" type="button" aria-label="Mostrar contraseña" aria-controls="contrasena" aria-pressed="false" hidden>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>
                <div class="button-row">
                    <button class="submit-button" type="submit">Iniciar sesión</button>
                    <button class="submit-button register" type="button" onclick="window.location.href='register.php'">Registrarme</button>
                </div>
            </form>
            <p class="form-note"><span class="status-dot"></span> Usa el correo electrónico de tu cuenta.</p>
        </section>
    </main>
    <footer class="site-footer"><span>GUITARIXTA</span><span>Un nuevo camino comienza aquí.</span><span class="footer-number">01 / ACCESO</span></footer>
</body>
</html>
