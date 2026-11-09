-- Ejecutar en la base existente de Guitarixta. No borra ni altera datos anteriores.
-- El QR del carrito guarda una selección privada durante siete días.
CREATE TABLE IF NOT EXISTS carrito_qr (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 token CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 usuario_origen VARCHAR(40) NOT NULL,
 usuario_id INT UNSIGNED NOT NULL,
 producto_id INT UNSIGNED NOT NULL,
 producto VARCHAR(150) NOT NULL,
 cantidad SMALLINT UNSIGNED NOT NULL,
 precio_centavos BIGINT UNSIGNED NOT NULL,
 actualizado_en DATETIME NOT NULL,
 expira_en DATETIME NOT NULL,
 PRIMARY KEY (id),
 UNIQUE KEY qr_token (token),
 UNIQUE KEY qr_usuario_producto (usuario_origen, usuario_id, producto_id),
 KEY qr_expira (expira_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
