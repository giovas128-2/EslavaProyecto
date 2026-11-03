<?php
function conectarBaseDatos(array $config): PDO
{
    return new PDO(
        'mysql:host=' . $config['host'] . ';port=' . (int) $config['port'] .
        ';dbname=' . $config['database'] . ';charset=utf8mb4',
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES => false]
    );
}
