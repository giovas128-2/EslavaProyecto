<?php
// Datos de MySQL/MariaDB en Laragon. Este puerto NO es el de Apache (80/90).
return [
    'host' => '127.0.0.1',
    'port' => 4400,
    'database' => 'proyectoeslava',
    'username' => 'root',
    'password' => '',
    // Compatible con la tabla de tu práctica si guardaste contraseñas como texto.
    // Para una aplicación publicada, utiliza password_hash y cambia esto a false.
    'permitir_password_texto' => true,
];
