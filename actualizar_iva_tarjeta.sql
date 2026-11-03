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
