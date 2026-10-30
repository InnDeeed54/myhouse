<?php
define('USER', 'root');
define('PASSWORD', '');
define('HOST', 'localhost');
define('DATABASE', 'myhouse');

try {
    $connection = new PDO("mysql:host=".HOST.";dbname=".DATABASE.";charset=utf8mb4", USER, PASSWORD);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    exit("Ошибка подключения к БД: " . $e->getMessage());
}
?>
