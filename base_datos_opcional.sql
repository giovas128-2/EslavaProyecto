-- SOLO si todavía no has creado la base o la tabla. No borra los datos existentes.
CREATE DATABASE IF NOT EXISTS `proyectoeslava` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `proyectoeslava`;
CREATE TABLE IF NOT EXISTS `login` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `correo` VARCHAR(254) NOT NULL UNIQUE,
    `contraseña` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `registroUsuarios` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `apellidoPaterno` VARCHAR(100) NOT NULL,
    `apellidoMaterno` VARCHAR(100) NOT NULL,
    `edad` TINYINT UNSIGNED NOT NULL,
    `genero` VARCHAR(40) NOT NULL,
    `correoElectronico` VARCHAR(254) NOT NULL UNIQUE,
    `contrasena` VARCHAR(255) NOT NULL,
    `fechaRegistro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ultimoAcceso` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Este archivo NO crea usuarios automáticamente.
-- Agrega tus propios datos desde HeidiSQL/phpMyAdmin en la tabla login.
