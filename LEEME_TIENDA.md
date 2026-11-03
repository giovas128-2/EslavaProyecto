# Guitarixta — tienda, carrito y pedidos

## Actualización del 18 de octubre de 2026: IVA y tarjeta animada

Si ya instalaste el ZIP anterior, respalda tu base e importa **actualizar_iva_tarjeta.sql** en HeidiSQL. Después reemplaza los archivos del proyecto conservando tu `includes/comercio_local.php`. Agrega a ese archivo `stripe_public` con tu clave pública `pk_test_...` o `pk_live_...`, del mismo modo y cuenta que `stripe_secret`. Para una instalación nueva, `instalacion_completa.sql` ya incluye esta migración; `actualizar_tienda.sql` también la incluye.

- Los precios de productos se consideran **antes de IVA**. Se agrega **16% sobre el subtotal de productos**, redondeado una sola vez al centavo. El envío se mantiene en **$30 MXN** por pedido; recoger cuesta $0. Ejemplo: productos $1,000 + IVA $160 + envío $30 = **$1,190**.
- IVA, porcentaje y total se guardan como importes fijos del pedido. Los pedidos anteriores mantienen sus importes y muestran IVA 0%; no se recalculan ni se vuelve a cobrar sobre ellos.
- La nueva pantalla `page/pagoTarjeta.php` usa el diseño azul de las capturas, nombre reflejado, tarjeta con giro 3D, campos Stripe dentro de la tarjeta, estados de carga/error y confirmación con ticket. Tiene adaptación móvil y respeta movimiento reducido.
- **Diferencia respecto al video:** número y vencimiento se escriben directamente en la tarjeta, en campos seguros de Stripe. El botón CVC gira al reverso y permite escribir allí. No hay un segundo campo HTML que copie el número o CVC fuera del iframe de Stripe. El CVC puede aparecer enmascarado por Stripe. El nombre sí se refleja desde el campo lateral. No se guarda ninguna tarjeta para compras futuras.
- La animación de “Pagado” aparece únicamente cuando el servidor verifica el pago o ya tiene un estado pagado confirmado. No hay confirmaciones por temporizador ni por hacer clic. En modo de prueba siempre se indica que no hubo cobro real. El PDF es un ticket de pedido, no un CFDI.
- Agrega a tu webhook los eventos `payment_intent.succeeded`, `payment_intent.canceled`, `payment_intent.payment_failed` y `payment_intent.processing`. Conserva los eventos de Checkout para pedidos antiguos aún pendientes.

## 1. Instalar en tu Laragon

1. Respalda tu carpeta anterior y la base `proyectoeslava`.
2. Copia **EslavaProyecto** a `C:\laragon\www\EslavaProyecto`, reemplazando los archivos anteriores.
3. Inicia Apache y MySQL. Se conservaron `127.0.0.1`, puerto MySQL **4400**, base `proyectoeslava`, usuario `root` y contraseña vacía en `includes/config.php`.
4. Si usabas el ZIP anterior y ya importaste sus tablas, importa **actualizar_tienda.sql** en HeidiSQL o phpMyAdmin. Si comienzas de cero, importa **instalacion_completa.sql**. Estos scripts no borran datos. Para estructuras modificadas manualmente, compara las columnas antes de importar.
5. Opcionalmente importa **catalogo_ejemplo.sql** para cargar las cuatro nuevas guitarras con sus fotos BLOB. No duplica los ejemplos por su código. Los nombres, precios y existencias son ilustrativos: están deshabilitados para compra hasta que revises sus datos y actives **Disponible para venta** en Administración → Productos → Editar. Las fotografías editoriales ya vienen en la carpeta.
6. Abre `http://localhost/EslavaProyecto/` o `http://localhost:90/EslavaProyecto/`, según el puerto de **Apache**. El 4400 es de MySQL, no del navegador.
7. Ingresa con tu administrador existente. No cambié contraseñas ni creé credenciales predeterminadas. Para una instalación vacía, las instrucciones para insertar el primer administrador están en `base_datos_admins.sql`.
8. En Administración → Productos, ajusta **precio real, stock disponible, descripción y categoría**, y activa la venta. El precio mínimo admitido es $10 MXN. Las cuentas normales pueden registrarse desde el login.
9. Copia `includes/comercio_local.example.php` a `includes/comercio_local.php`. Escribe la URL real de tu instalación y el domicilio donde podrán recoger. Las claves se configuran en ese archivo o mediante variables de entorno. Solo la clave pública `stripe_public` se entrega a Stripe.js; la clave secreta y el secreto del webhook permanecen en PHP.

PHP 8.1 o superior, MySQL 8 / MariaDB con InnoDB. Extensiones PHP: PDO MySQL, cURL, fileinfo, iconv, zlib. Para imágenes: `upload_max_filesize=4M`, `post_max_size=8M` y MySQL `max_allowed_packet` al menos 8 MB. Reinicia Laragon después de modificar PHP.

## 2. Qué incluye

- Diseño negro, blanco y verde inspirado en la referencia: portada con guitarra, categorías, tarjetas, banners y versión móvil.
- Carrito SVG en la parte superior derecha, indicador rojo con el total de piezas y panel lateral con foto, cantidad, precio, nombre y código de barras Code 39.
- Carrito guardado en la sesión del navegador; inicia sesión para confirmar el pedido. Un cierre de sesión vacía el carrito.
- Recoger en tienda: sin cargo. Domicilio: **$30 MXN por pedido**, independientemente del número de guitarras.
- Efectivo al recoger o al recibir, con resumen, folio y PDF marcado **PENDIENTE DE PAGO EN EFECTIVO**.
- Tarjeta mediante Stripe Elements y PaymentIntents: campos alojados por Stripe dentro de la tarjeta animada. Los pedidos históricos siguen utilizando Stripe Checkout alojado. La aplicación no recibe ni guarda número completo, vencimiento o CVV.
- Historial de pedidos, consulta de estado y descarga de tickets PDF reales desde PHP (no depende de guardar la página como PDF). Cada producto incluye su fotografía actual del catálogo; para convertir imágenes PNG/WebP a miniaturas JPEG en el PDF, PHP necesita la extensión GD.
- Dirección, referencias y coordenadas del punto de entrega; mapa OpenStreetMap también en el detalle administrativo.
- Administración → Pedidos y entregas, campana con contador de novedades (se actualiza cada 20 segundos mientras el panel está abierto), detalle de productos/cantidades/importes y cambios de estado.
- Se conservaron usuarios, empleados, administradores, bitácora y visor binario de imágenes. Las imágenes del catálogo son públicas; los datos de pedidos y tickets requieren la cuenta propietaria o un administrador.

## 3. Activar tarjeta: no basta con importar el SQL

La integración está implementada, pero **no puede cobrar sin tu cuenta y claves de Stripe**. No hay pagos simulados que se presenten como reales. El botón de tarjeta queda inactivo sin configuración; el efectivo puede utilizarse de forma independiente.

### Prueba en Laragon

1. Crea/configura tu cuenta en Stripe y obtén una clave secreta de prueba (`sk_test_...`) y la clave pública correspondiente (`pk_test_...`).
2. Configura `base_url` con tu URL local exacta en `includes/comercio_local.php` y pega allí `stripe_secret` y `stripe_public` de prueba. Conserva `stripe_live_enabled` en `false`.
3. Instala e inicia sesión en Stripe CLI, y ejecuta (ajusta el puerto de Apache):

```text
stripe listen --forward-to http://localhost:90/EslavaProyecto/components/stripeWebhook.php
```

4. Copia el secreto `whsec_...` que entrega ese comando a `stripe_webhook_secret`. Debe corresponder al webhook que está reenviando los eventos.
5. Mantén Laragon y Stripe CLI abiertos. Completa pruebas con los datos de tarjeta de prueba de la documentación oficial de Stripe. La interfaz, los pedidos y el ticket indicarán **PRUEBA / SIN COBRO REAL**.
6. Comprueba un pago aprobado, uno rechazado y una sesión vencida antes de cambiar a producción. No uses tarjetas reales en modo de prueba.

### Cobros reales

1. Completa la activación de tu cuenta Stripe y sus datos bancarios.
2. Aloja el proyecto PHP/MySQL en un servidor público con HTTPS. Una instalación `localhost` no es accesible para los compradores ni para los webhooks públicos.
3. Configura `base_url` con tu dominio HTTPS, `stripe_secret` con la clave secreta **live**, `stripe_public` con la clave pública **live**, `stripe_webhook_secret` con el secreto del endpoint **live**, y `stripe_live_enabled` en `true`.
4. Registra en Stripe el endpoint público:

```text
https://TU-DOMINIO/EslavaProyecto/components/stripeWebhook.php
```

5. Suscribe `payment_intent.succeeded`, `payment_intent.canceled`, `payment_intent.payment_failed` y `payment_intent.processing`. Para los pedidos históricos conserva también: `checkout.session.completed`, `checkout.session.async_payment_succeeded` y `checkout.session.expired`.
6. Revisa los productos de ejemplo y confirma precios, stock y domicilio de recogida antes de publicarlos. Stripe aplica sus propias condiciones y comisiones; los $30 del envío no son una comisión de Stripe.

El webhook verifica la firma y consulta el pago directamente a Stripe; solo acepta importes, moneda y modo que coincidan con el pedido. No marca como pagado por un parámetro de la URL. La nueva pantalla consulta el estado al terminar de pagar; al regresar de una autenticación del banco, el usuario puede consultar el estado o continuar con el mismo intento. El servidor nunca acepta como prueba de pago un estado enviado por el navegador. Si falta la confirmación, informa que el pago está pendiente.

No compartas claves secretas por correo, chats o repositorios. `includes/.htaccess` impide servir los archivos de configuración directamente bajo Apache; en otro servidor configura una protección equivalente. No desactives la verificación TLS de cURL; si falla un certificado, configura `curl.cainfo` en PHP con un paquete CA válido.

## 4. Stock, reservas y estados

`productos.stock` representa **piezas disponibles**. Agregar al carrito no reserva. Confirmar un pedido verifica los productos y cantidades dentro de una transacción con bloqueos de filas, y descuenta el stock de todos los artículos de forma atómica.

- **Efectivo:** queda `pendiente_efectivo`, reservado. El administrador confirma el dinero recibido y después marca entregado. Puede cancelar un pedido todavía no pagado; devuelve las piezas una sola vez. No vence automáticamente: el administrador gestiona los apartados no recogidos.
- **Tarjeta:** queda `pendiente_tarjeta`, con piezas reservadas mientras se autoriza. La reserva tiene vencimiento aproximado de 35 minutos. El script de conciliación intenta cancelar en Stripe los pagos que todavía requieren datos/confirmación/autenticación. Solo después de recibir una cancelación confirmada devuelve el stock una vez. Un pago en procesamiento conserva su reserva hasta resolverse. Al confirmar Stripe pasa a `pagado` sin descontar stock de nuevo.
- Si una llamada a Stripe queda inconclusa, el pedido se conserva para reintentar o reconciliar. No se libera inventario solo por cerrar la pestaña ni por recibir un error de red.
- Los pedidos ya pagados no se cancelan ni reembolsan desde este panel. Los reembolsos se tramitan en Stripe y no se sincronizan automáticamente en esta versión. Esta versión tampoco incluye gestión de disputas.
- No se elimina un producto con historial de pedidos; se desactiva la venta para preservar historial y reservas.
- La edición del stock detecta si cambió desde que abriste el formulario, para evitar sobrescribir una compra concurrente.
- Las solicitudes repetidas del mismo formulario conservan el pedido original; las notificaciones repetidas de Stripe no duplican descuento o devolución de stock.

### Sincronización de respaldo

Programa cada 5 minutos (Programador de tareas de Windows o cron en el servidor):

```text
php C:\laragon\www\EslavaProyecto\scripts\sincronizar_pagos.php
```

Usa la ruta a tu `php.exe` si no está en PATH. Programa este comando cada 5 minutos. Consulta pagos pendientes y concilia tanto PaymentIntents nuevos como Checkout históricos. Cada pedido usa un único intento e idéntica clave de idempotencia. Si la creación se interrumpe, reintenta con esa clave; después de 18 horas adicionales al vencimiento busca el intento por su folio y nunca crea uno nuevo. Si no puede recuperarlo o Stripe no responde, conserva la reserva y requiere conciliación por el administrador. No cambies la cuenta de Stripe mientras tengas pedidos pendientes. El webhook es la vía principal; mantén funcionando ambas.

## 5. Domicilio y OpenStreetMap

El usuario escribe su domicilio y coloca un pin en el mapa. También puede usar **Usar mi ubicación** (permiso del navegador) y arrastrar el pin, o introducir coordenadas. No envía la dirección escrita a un servicio de geocodificación. El punto no se confirma hasta que el usuario lo selecciona; no se supone que el centro inicial del mapa sea su domicilio.

Leaflet está incluido localmente. El mapa descarga teselas públicas de OpenStreetMap por internet y muestra su atribución. `map_tiles` permite cambiar el proveedor si tu tráfico lo requiere; no hay descarga masiva de mapas ni navegación del repartidor. El administrador ve el punto y los datos para coordinar la entrega. No se contrata paquetería ni se avisa a un repartidor externo automáticamente.

## 6. Archivos principales

- `page/tienda.php`, `producto.php`: catálogo y detalle.
- `page/checkout.php`, `misPedidos.php`, `pedido.php`: compra e historial.
- `page/pedidosAdmin.php`: notificaciones y gestión.
- `includes/comercio.php`: importes, inventario, pedidos e integración Stripe.
- `components/tiendaApi.php`, `stripeWebhook.php`: operaciones y confirmaciones.
- `includes/ticketPdf.php`, `components/ticket.php`: PDF con fuentes incluidas.
- `assets/css/tienda.css`, `assets/js/tienda.js`, `assets/js/checkout.js`: interfaz.
- `actualizar_tienda.sql`, `instalacion_completa.sql`, `catalogo_ejemplo.sql`: base de datos y ejemplos.

## 7. Verificación y límites de esta entrega

Se ejecutó el código PHP con un entorno PHP WebAssembly y una base de pruebas SQLite adaptada, incluyendo 30 comprobaciones de importes, reservas, rechazo por stock insuficiente, idempotencia, devoluciones, acceso a pedidos y transiciones de confirmaciones de pago. Los eventos de Stripe de esas pruebas son datos sintéticos: no se ejecutó un cobro real ni una llamada autenticada a Stripe. Se verificó el IVA en el PDF generado. La nueva pantalla se probó en navegador de escritorio y móvil con un sustituto de Stripe.js y respuestas simuladas del servidor: nombre, entrada sobre tarjeta, giro CVC, espera sin éxito prematuro y confirmación. Esto verifica la interfaz y sus estados; no sustituye una prueba con los iframes reales, 3DS, credenciales y webhook de Stripe.

La base de producción permanece en MySQL/MariaDB. Las pruebas de SQLite no validan los bloqueos concurrentes de InnoDB ni reemplazan importar el SQL y probar contra tu Laragon. Debes comprobar la integración de Stripe con tus credenciales de prueba y después el webhook de producción. Esta entrega no está publicada en internet.

## Referencias técnicas

- Stripe PaymentIntents: https://docs.stripe.com/api/payment_intents/create
- Confirmación segura y 3DS: https://docs.stripe.com/js/payment_intents/confirm_card_payment
- Stripe Checkout histórico: https://docs.stripe.com/api/checkout/sessions/create
- Webhooks: https://docs.stripe.com/webhooks
- Pruebas de Stripe: https://docs.stripe.com/testing
- Política de teselas OSM: https://operations.osmfoundation.org/policies/tiles/
- Leaflet: https://leafletjs.com/

Leaflet y DejaVu incluyen sus licencias en `assets/vendor/leaflet/LICENSE` y `assets/fonts/LICENSE-DejaVu.txt`. Las fotos provienen de los archivos que compartiste; nombres y precios del catálogo de ejemplo son ilustrativos.
