# Actualización: ticket por Gmail y gráficas de usuarios

Esta entrega parte de tu archivo `EslavaProyecto(3).zip`. Solo añade las funciones solicitadas. Conserva el cálculo del IVA, el catálogo, los pagos, la bitácora, las cuentas y los demás flujos.

## 1. Actualizar la instalación existente

1. Respalda tu carpeta y base de datos.
2. En HeidiSQL, abre la conexión MySQL **127.0.0.1:4400** y ejecuta `actualizar_gmail_graficas.sql` sobre **proyectoeslava**. Este archivo crea únicamente `ticket_envios`; no borra ni recalcula datos.
3. Copia los archivos del proyecto a tu carpeta de Laragon, conservando tus archivos locales de configuración (por ejemplo `includes/comercio_local.php`).
4. Para una instalación completamente nueva: primero sigue la instalación anterior con `instalacion_completa.sql` y después ejecuta `actualizar_gmail_graficas.sql`.
5. Usa Ctrl+F5 al abrir las pantallas para cargar los nuevos estilos y scripts.

## 2. Configurar Gmail

El ticket se envía desde la cuenta de Gmail de la tienda, como **archivo PDF adjunto**, al correo que quedó guardado en el pedido. El cliente no inicia sesión en Gmail dentro de la tienda. El destino puede ser de Gmail o de otro proveedor; no se puede escribir un destinatario arbitrario en el formulario de envío.

1. Activa la verificación en dos pasos de la cuenta remitente de Google.
2. Crea una **contraseña de aplicación** para esta instalación. No uses tu contraseña habitual. Algunas cuentas escolares, de empresa o con protección avanzada no permiten esta opción; en ese caso necesitarás una cuenta que la admita o una integración OAuth adicional (no incluida).
3. Copia `includes/gmail_local.example.php` como **includes/gmail_local.php**.
4. Escribe en `usuario` el correo remitente y en `password_aplicacion` la contraseña de aplicación de 16 caracteres. Puedes conservar o cambiar el nombre `Guitarixta`.
5. En PHP de Laragon habilita **openssl**, conserva la verificación de certificados y permite la conexión saliente a **smtp.gmail.com, puerto 587, STARTTLS**. Reinicia Apache si cambias php.ini. Si falla un certificado, configura un almacén CA válido; no desactives TLS.

También se admiten las variables de entorno `GX_GMAIL_USUARIO` y `GX_GMAIL_APP_PASSWORD`. El archivo local está excluido de Git y protegido por el `.htaccess` existente de `includes`. No compartas ese archivo con sus credenciales. La pantalla de envío comprueba OpenSSL y muestra un aviso si falta; confirma que la extensión esté habilitada en el `php.ini` que usa Apache y reinícialo después del cambio.

PHPMailer **7.1.1** viene incluido en `includes/vendor/phpmailer`; no hace falta instalar Composer. Se conserva su licencia en esa carpeta.

## 3. Cómo usar las dos opciones del ticket

- Después de un pago confirmado con tarjeta, la pantalla de confirmación muestra **Descargar ticket PDF** y **Enviar por Gmail**.
- En **Mis pedidos → Ver pedido**, un pedido en efectivo muestra ambas opciones desde que se crea. El ticket enviado por Gmail indica que el pago está pendiente y cuándo debe pagarse.
- En compras con tarjeta, el envío por Gmail se habilita cuando Stripe confirma el pago. La descarga se mantiene disponible según el estado del pedido.
- Al elegir Gmail se muestra el destinatario y el total; al pulsar **Enviar ticket por Gmail** se envía el archivo adjunto. No se envían correos automáticamente al comprar.
- Los pedidos de prueba llevan **PRUEBA SIN COBRO REAL** tanto en el asunto del correo como en el PDF.
- El mensaje de éxito significa que Gmail aceptó el correo para envío; revisa también spam. No garantiza entrega en bandeja principal.

Se impiden los envíos simultáneos y las repeticiones del mismo formulario. Un reenvío intencional requiere volver a la pantalla, esperar al menos **5 minutos**, y se permiten como máximo **3 intentos por pedido y día**. Se guardan el destinatario, estado, identificador del mensaje y fechas; no se guardan contraseñas ni el PDF en la tabla.

Si se interrumpe la comunicación durante el envío, el registro queda `incierto` o `enviando` y no se reenvía automáticamente. El administrador debe revisar Gmail y la recepción antes de desbloquearlo. Si comprobó que **no se envió**, puede cambiar solo ese registro a `error` en `ticket_envios`; después de la espera podrá solicitarse un nuevo envío. Si confirmó que sí se envió, puede registrar `enviado` y su fecha. No borres pedidos para solucionar un error de correo.

## 4. Gráficas

Abre **Administración → Usuarios → Gráficas**. Esta pantalla conserva la temática verde, crema y oscura del proyecto.

- **Pedidos por fecha:** cantidad de pedidos o importe cobrado real, agrupado por día o mes. Pulsar una barra filtra ese periodo.
- **Guitarras pedidas:** piezas por producto según los filtros; si seleccionas todos los estados, incluye piezas de pedidos pendientes o cancelados. Para ver compras confirmadas utiliza el filtro de estado.
- **Entradas y salidas:** cantidades por cuenta, con intentos fallidos separados. Pulsar una cuenta filtra sus compras y accesos.
- **¿Quién compró qué?:** usuario comprador, identidad de la cuenta, destinatario si es diferente, productos, cantidades, precios unitarios, fecha, día de la semana, hora de creación, fecha de confirmación, estado y total. Los pedidos se paginan de 12 en 12, sin eliminar el resto del historial.
- **Actividad por usuario:** tabla con entradas exitosas, salidas, fallidos y última fecha de actividad.

Filtros disponibles: últimos 7/30/90 días, todo el historial o fechas personalizadas, usuario, estado y pedidos reales/de prueba. El botón **Actualizar** vuelve a consultar la base.

El periodo de pedidos se aplica a su **fecha de creación**, y el de accesos a la **fecha de la bitácora**. Estado y tipo de pedido no filtran la bitácora. Las fechas y horas conservan la zona Ciudad de México del proyecto.

**Cobrado real** suma los totales (IVA y envío incluidos) de pedidos pagados o entregados y excluye los de prueba. No es un reporte de liquidación bancaria ni descuenta comisiones, devoluciones o disputas externas, porque esos movimientos no se registran en el proyecto actual.

Las entradas cuentan únicamente inicios de sesión exitosos. Las salidas cuentan el botón **Cerrar sesión** registrado en bitácora; cerrar el navegador o expirar una sesión no crea un evento de salida. Los intentos fallidos con una cuenta reconocida se asignan a esa cuenta; los demás se agrupan como no identificados. No se inventan eventos faltantes ni se cambia el login. Las cuentas eliminadas conservan su identidad histórica y no se mezclan solo por compartir correo.

Las gráficas se dibujan localmente con HTML/CSS/JavaScript, sin dependencias de gráficas externas. Solo administradores existentes pueden abrir la página y su endpoint de datos.

## 5. Alcance y verificación

Archivos existentes modificados:
- `page/admin.php`: botón Gráficas dentro del apartado Usuarios.
- `page/pedido.php`: opciones PDF y Gmail en los estados permitidos.
- `page/enviarTicket.php`: envío de pedidos en efectivo pendientes y diagnóstico de requisitos Gmail.
- `page/checkout.php`: aviso de las opciones disponibles al terminar la compra.
- `includes/ticketCorreo.php`: elegibilidad del ticket y comprobación de OpenSSL.
- `page/pagoTarjeta.php`: enlace Gmail en la confirmación.
- `assets/css/pagoTarjeta.css`: permite que los botones de esa confirmación se acomoden en varias filas.
- `.gitignore`: excluye las credenciales locales de Gmail.

Los demás cambios corresponden a los módulos de correo y reportes, PHPMailer, configuración y migración incluidos con la actualización.

Se verificaron cálculos y filtros de reportes, separación entre comprador y destinatario, identificación de cuentas, permisos sobre pedidos, estados permitidos para el envío, PDF adjunto MIME, repetición y espera entre solicitudes. Las pruebas de datos usan una base SQLite adaptada; no sustituyen comprobar la migración contra MySQL/Laragon. También se revisaron las pantallas en escritorio y teléfono.

**No se envió ningún correo real ni se probaron credenciales Gmail.** Para comprobar la entrega real, configura tu cuenta y envía un ticket a un correo de prueba que controles usando la interfaz.

Referencias oficiales:
- Gmail SMTP: https://support.google.com/a/answer/176600?hl=es
- Contraseñas de aplicaciones: https://support.google.com/accounts/answer/185833?hl=es
- PHPMailer: https://github.com/PHPMailer/PHPMailer/releases/tag/v7.1.1
