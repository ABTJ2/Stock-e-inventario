<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';

$conexion = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=stock_inventario;charset=' . DB_CHARSET,
    DB_USER,
    DB_PASS,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
