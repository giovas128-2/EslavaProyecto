USE proyectoeslava;
SET @gx_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='productos' AND COLUMN_NAME='precio')=0, 'ALTER TABLE productos ADD COLUMN `precio` DECIMAL(10,2) NOT NULL DEFAULT 0.00', 'SELECT 1');
PREPARE gx_stmt FROM @gx_sql; EXECUTE gx_stmt; DEALLOCATE PREPARE gx_stmt;
SET @gx_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='productos' AND COLUMN_NAME='descripcion')=0, 'ALTER TABLE productos ADD COLUMN `descripcion` VARCHAR(600) NOT NULL DEFAULT ''''', 'SELECT 1');
PREPARE gx_stmt FROM @gx_sql; EXECUTE gx_stmt; DEALLOCATE PREPARE gx_stmt;
SET @gx_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='productos' AND COLUMN_NAME='categoria')=0, 'ALTER TABLE productos ADD COLUMN `categoria` VARCHAR(40) NOT NULL DEFAULT ''Guitarras''', 'SELECT 1');
PREPARE gx_stmt FROM @gx_sql; EXECUTE gx_stmt; DEALLOCATE PREPARE gx_stmt;
SET @gx_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='productos' AND COLUMN_NAME='activo')=0, 'ALTER TABLE productos ADD COLUMN `activo` TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE gx_stmt FROM @gx_sql; EXECUTE gx_stmt; DEALLOCATE PREPARE gx_stmt;
SET @gx_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='productos' AND COLUMN_NAME='codigo')=0, 'ALTER TABLE productos ADD COLUMN `codigo` VARCHAR(32) NULL', 'SELECT 1');
PREPARE gx_stmt FROM @gx_sql; EXECUTE gx_stmt; DEALLOCATE PREPARE gx_stmt;
UPDATE productos SET codigo=CONCAT('GX',LPAD(id,8,'0')) WHERE codigo IS NULL OR codigo='';
CREATE TABLE IF NOT EXISTS pedidos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 folio VARCHAR(24) NOT NULL UNIQUE,
 usuario_id INT UNSIGNED NOT NULL, usuario_origen VARCHAR(40) NOT NULL,
 nombre VARCHAR(120) NOT NULL, correo VARCHAR(254) NOT NULL, telefono VARCHAR(25) NOT NULL,
 metodo ENUM('efectivo','tarjeta') NOT NULL,
 entrega ENUM('tienda','domicilio') NOT NULL,
 direccion VARCHAR(600) NOT NULL DEFAULT '', referencias VARCHAR(300) NOT NULL DEFAULT '',
 latitud DECIMAL(10,7) NULL, longitud DECIMAL(10,7) NULL,
 subtotal_centavos BIGINT UNSIGNED NOT NULL, envio_centavos INT UNSIGNED NOT NULL,
 total_centavos BIGINT UNSIGNED NOT NULL,
 estado ENUM('pendiente_efectivo','pendiente_tarjeta','pagado','cancelado','expirado','entregado') NOT NULL,
 pagado_en DATETIME NULL, creado_en DATETIME NOT NULL, vencimiento_unix BIGINT NULL,
 solicitud VARCHAR(64) NOT NULL UNIQUE,
 stripe_session VARCHAR(255) NULL UNIQUE, stripe_url TEXT NULL, stripe_payload MEDIUMTEXT NULL,
 stripe_modo VARCHAR(8) NULL, admin_visto TINYINT NOT NULL DEFAULT 0,
 INDEX propietario(usuario_origen,usuario_id), INDEX avisos(admin_visto,estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS pedido_detalles (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 pedido_id BIGINT UNSIGNED NOT NULL, producto_id INT UNSIGNED NOT NULL,
 producto VARCHAR(150) NOT NULL, codigo VARCHAR(32) NOT NULL,
 cantidad INT UNSIGNED NOT NULL, precio_centavos BIGINT UNSIGNED NOT NULL,
 INDEX producto(producto_id),
 FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
 FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Importar después de actualizar_tienda.sql. No recalcula pedidos anteriores.
USE proyectoeslava;
SET @gx_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pedidos' AND COLUMN_NAME='iva_centavos')=0, 'ALTER TABLE pedidos ADD COLUMN `iva_centavos` BIGINT UNSIGNED NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE gx_stmt FROM @gx_sql; EXECUTE gx_stmt; DEALLOCATE PREPARE gx_stmt;
SET @gx_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pedidos' AND COLUMN_NAME='iva_porcentaje')=0, 'ALTER TABLE pedidos ADD COLUMN `iva_porcentaje` TINYINT UNSIGNED NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE gx_stmt FROM @gx_sql; EXECUTE gx_stmt; DEALLOCATE PREPARE gx_stmt;
SET @gx_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pedidos' AND COLUMN_NAME='stripe_motor')=0, 'ALTER TABLE pedidos ADD COLUMN `stripe_motor` VARCHAR(16) NULL', 'SELECT 1');
PREPARE gx_stmt FROM @gx_sql; EXECUTE gx_stmt; DEALLOCATE PREPARE gx_stmt;
SET @gx_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pedidos' AND COLUMN_NAME='stripe_intent')=0, 'ALTER TABLE pedidos ADD COLUMN `stripe_intent` VARCHAR(255) NULL UNIQUE', 'SELECT 1');
PREPARE gx_stmt FROM @gx_sql; EXECUTE gx_stmt; DEALLOCATE PREPARE gx_stmt;
