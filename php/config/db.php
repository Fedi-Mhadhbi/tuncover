<?php
// /var/www/tuncover/php/config/db.php

define('DB_HOST', 'localhost');
define('DB_NAME', 'tuncover');
define('DB_USER', 'root');
define('DB_PASS', '');   // XAMPP default = no password

function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST
                 . ';dbname=' . DB_NAME
                 . ';charset=utf8mb4';

            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error'   => 'Database connection failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    return $pdo;
}