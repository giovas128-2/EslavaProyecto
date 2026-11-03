<?php
// Copia comercio_local.example.php a comercio_local.php para configurar esta instalación.
$configComercio = [
 'base_url' => getenv('GX_BASE_URL') ?: 'http://localhost/EslavaProyecto',
 'stripe_public' => getenv('STRIPE_PUBLISHABLE_KEY') ?: '',
 'stripe_secret' => getenv('STRIPE_SECRET_KEY') ?: '',
 'stripe_webhook_secret' => getenv('STRIPE_WEBHOOK_SECRET') ?: '',
 'stripe_live_enabled' => getenv('GX_STRIPE_LIVE_ENABLED') === '1',
 'envio_centavos' => 3000,
 'tienda_direccion' => 'Consulta el punto de entrega con la tienda.',
 'map_tiles' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
];
if (is_file(__DIR__.'/comercio_local.php')) { $configComercio = array_replace($configComercio, require __DIR__.'/comercio_local.php'); }
return $configComercio;
