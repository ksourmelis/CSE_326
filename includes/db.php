<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'kyriakos');
define('DB_PASSWORD', '1234');
define('DB_NAME', 'pothen_esxes_db');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASSWORD
    );
} catch (PDOException $e) {
    exit('Database connection failed. Please try again later.');
}
?>
