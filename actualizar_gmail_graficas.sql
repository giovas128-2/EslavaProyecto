-- Ejecutar una vez en tu base existente. Puede repetirse sin borrar datos.
USE proyectoeslava;
CREATE TABLE IF NOT EXISTS ticket_envios (
 pedido_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
 destinatario VARCHAR(254) NOT NULL,
 estado VARCHAR(16) NOT NULL DEFAULT 'nuevo',
 solicitud CHAR(64) NOT NULL,
 dia DATE NOT NULL,
 intentos_dia INT UNSIGNED NOT NULL DEFAULT 0,
 actualizado_en DATETIME NOT NULL,
 enviado_en DATETIME NULL,
 message_id VARCHAR(255) NULL,
 FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Las gráficas consultan pedidos, pedido_detalles y bitacora; no duplican sus datos.
