CREATE DATABASE IF NOT EXISTS `proyectoeslava` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `proyectoeslava`;

CREATE TABLE IF NOT EXISTS `registrarAdmins` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `apellidoPaterno` VARCHAR(100) NOT NULL,
    `apellidoMaterno` VARCHAR(100) NOT NULL,
    `edad` TINYINT UNSIGNED NOT NULL,
    `genero` VARCHAR(40) NOT NULL,
    `correoElectronico` VARCHAR(254) NOT NULL UNIQUE,
    `contrasena` VARCHAR(255) NOT NULL,
    `puesto` VARCHAR(100) NOT NULL,
    `fechaRegistro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ultimoAcceso` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Genera el hash de la contraseña con PHP y pega el resultado en `contrasena`:
-- php -r "echo password_hash('TuContrasena', PASSWORD_DEFAULT), PHP_EOL;"
-- Inserta cada admin con SQL, reemplazando los valores de ejemplo y el hash:
-- INSERT INTO `registrarAdmins`
-- (`nombre`, `apellidoPaterno`, `apellidoMaterno`, `edad`, `genero`, `correoElectronico`, `contrasena`, `puesto`)
-- VALUES ('Nombre', 'Apellido paterno', 'Apellido materno', 30, 'Prefiero no decirlo', 'admin@tienda.com', 'PEGA_AQUI_EL_HASH', 'Administrador');