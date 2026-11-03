# Empieza aquí

Lee **LEEME_TIENDA.md** para instalar esta versión y activar los pagos.

- Si ya instalaste la tienda anterior: importa `actualizar_iva_tarjeta.sql` antes de reemplazar el código. Conserva tu `includes/comercio_local.php` y agrega `stripe_public`.
- Base existente del panel anterior: importa `actualizar_tienda.sql`.
- Base nueva: importa `instalacion_completa.sql`.
- Fotos/productos nuevos: importa `catalogo_ejemplo.sql`; revisa los datos y activa la venta desde el administrador.

Host MySQL `127.0.0.1`, puerto `4400`. El puerto de Apache es independiente.

**Tarjeta requiere configurar tu cuenta Stripe, claves y webhook.** El efectivo puede funcionar sin Stripe.
