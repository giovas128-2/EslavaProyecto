# Actualización: carrito con QR y reporte de ventas

Esta actualización se aplica a la tienda PHP existente. No reinstales la base de datos ni vuelvas a importar los respaldos anteriores.

## Qué agrega

- Carrito en tabla: producto, stock entendido como cantidad seleccionada, imagen, QR y total de la línea.
- La misma tabla en el detalle de cada pedido, con cantidad comprada y precio histórico.
- QR que abre una página adaptable al celular. Requiere iniciar sesión con la misma cuenta que creó la selección o el pedido. Luego del acceso regresa automáticamente al producto.
- Antes de comprar, inicia sesión en la computadora para generar el QR. El carrito de invitado sigue funcionando, pero su QR se habilita al ingresar.
- Cada QR de carrito guarda una vista previa durante siete días. Se actualiza al volver a cargar el carrito desde el navegador de origen. No reserva piezas, no confirma pago y no sincroniza el carrito completo entre dispositivos.
- QR de pedido permanente mientras exista su detalle. Solo el comprador puede consultar sus datos; no basta conocer o cambiar el ID.
- Menú administrador → Reporte. Filtros Desde/Hasta (ambos incluidos), estado y tipo real/prueba; resumen y tabla paginada de pedidos con cliente, productos, cantidades, fechas, pago, subtotal, IVA, envío y total.
- El periodo se aplica a la creación del pedido, en horario de Ciudad de México. Ventas cobradas = pedidos reales pagados o entregados. Las pruebas, pendientes, cancelaciones y expiraciones no se suman a lo cobrado.
- Los importes por producto son cantidad × precio, antes del IVA y del envío. Se mantiene el cálculo existente del pedido: IVA 16% y envío de $30 MXN si corresponde.
- La imagen procede del producto actual; nombre, cantidad y precio del pedido proceden del detalle guardado.

## Instalar en Alwaysdata con el paquete de actualización

1. Descarga y extrae `Guitarixta_QR_Reportes.zip` en tu computadora.
2. En phpMyAdmin abre `guitarixta_proyectoeslava` → Importar. Selecciona **solo** `actualizar_qr_reportes.sql` del paquete (UTF-8, formato SQL) y ejecuta. Crea una tabla nueva `carrito_qr`. No borra pedidos, usuarios, productos ni imágenes. Se puede repetir porque usa CREATE TABLE IF NOT EXISTS.
3. Haz una copia de los cinco archivos existentes que se reemplazan (lista al final), guardándola fuera de la carpeta pública `www`. Es suficiente para volver al código anterior.
4. Sube el contenido de la carpeta `archivos` del ZIP a `/home/guitarixta/www/EslavaProyecto/` por SFTP. Combina las carpetas `assets`, `components`, `includes` y `page`, y confirma reemplazo solo de los archivos del paquete. No subas una carpeta `archivos` dentro del proyecto: copia su contenido.
5. El paquete no contiene ni reemplaza `includes/config.php`, `includes/comercio_local.php` o `includes/gmail_local.php`; conserva la conexión y claves configuradas en Alwaysdata.
6. Recarga la tienda con Ctrl + F5. No necesitas Composer, npm ni Laragon en Alwaysdata.

La versión del repositorio usada como base es `6c45edf5bbea2b8b0ba23a9057c1f84d32af511e`. Si desde entonces modificaste alguno de los cinco archivos que se reemplazan, combina esos cambios antes de sobrescribirlos.

## Comprobar

1. Inicia sesión como cliente y agrega dos unidades de una guitarra. Abre el carrito: deben verse cinco columnas, dos piezas y el importe multiplicado por dos.
2. Cambia la cantidad. Confirma que se actualizan el total y la selección que abre el QR.
3. Escanea el QR con un celular. Inicia sesión con la misma cuenta: debe abrirse la imagen, cantidad y total. Otra cuenta debe recibir un aviso de que el QR no está disponible.
4. Abre uno de los pedidos existentes desde Mis pedidos. Su tabla debe tener un QR por producto y conservar los precios y cantidades históricos. No hace falta generar un cobro real para probar.
5. Entra como administrador → Menú → Reporte. Para ver los pedidos del respaldo, selecciona un rango que incluya octubre de 2026. Revisa también un periodo sin pedidos, un rango invertido y el filtro Solo pruebas.
6. El celular debe abrir la tienda pública de Alwaysdata. Un QR generado desde localhost apunta a la computadora local y no sirve como enlace público.

## Archivos que reemplaza

- `assets/js/tienda.js`
- `components/verificarUsuario.php`
- `includes/adminMenu.php`
- `includes/shopHeader.php`
- `page/pedido.php`

## Archivos que agrega

- `assets/css/productoQr.css`
- `assets/js/productoQr.js`
- `assets/vendor/qrcode/qrcode.js` y `LICENSE`
- `components/qrCarrito.php`
- `includes/productoQr.php`
- `includes/reportes.php`
- `page/productoQr.php`
- `page/reporte.php`
- Migración `actualizar_qr_reportes.sql`.

## Verificación técnica

Se comprobó la sintaxis de PHP y JavaScript, controles de acceso del QR, vencimiento, fechas inclusivas y totales del reporte con datos sintéticos mediante PDO SQLite. Se probó la tabla del carrito con un DOM de prueba y la decodificación del QR. Las páginas se ejecutaron con PHP y una base de prueba local. La migración MySQL y la integración final deben verificarse en Alwaysdata; no se ha desplegado ni realizado ningún cobro real. La revisión visual en navegador no estuvo disponible en este entorno.

`tests/qr_reportes.php` permite repetir las pruebas de reglas con PHP y PDO SQLite en desarrollo; no necesita publicarse.

QR local: qrcode-generator 2.0.4, Kazuhiko Arase, MIT. Fuente: https://github.com/kazuhikoarase/qrcode-generator (commit 83b7e8fe3fddd3b0368dbafd6ce56995bd25e3c8). Los enlaces privados no se envían a servicios externos para generar el QR.
