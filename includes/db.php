<?php

define('DB_HOST', 'localhost');
<<<<<<< HEAD
define('DB_USER', 'test_user');
=======
define('DB_USER', 'florentia');
>>>>>>> 227c7cc6dc9e232bfe81c0ab936d4a400cf9cdd1
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
