<?php
return [
 'base_url' => 'http://localhost:90/EslavaProyecto', // Sin / final. Ajusta puerto de Apache.
 'stripe_public' => '', // pk_test_... o pk_live_..., del mismo modo y cuenta que la clave secreta.
 'stripe_secret' => '', // sk_test_... para pruebas; sk_live_... para cobros reales.
 'stripe_webhook_secret' => '', // whsec_... del endpoint o de Stripe CLI.
 'stripe_live_enabled' => false, // true solo con HTTPS público, webhook configurado y precios reales.
 'tienda_direccion' => 'ESCRIBE AQUÍ LA DIRECCIÓN REAL PARA RECOGER',
];
