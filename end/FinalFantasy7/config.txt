<?php
    define('USER', 'root');
    define('PASSWORD', ''); // Если OpenServer, попробуйте 'root'
    define('HOST', 'localhost');
    define('DATABASE', 'test');
    try {
        $connection = new PDO("mysql:host=".HOST.";dbname=".DATABASE, USER, PASSWORD);
    } catch (PDOException $e) {
        exit("Error: " . $e->getMessage());
    }
?>
