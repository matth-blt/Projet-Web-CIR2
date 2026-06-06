<?php 
    require_once __DIR__ . '/constants.php';

    class Database {
        public static function getConnection(): PDO {
            $dsn = 'mysql:host=' . DB_SERVER
                . ';dbname=' . DB_NAME
                . ';charset=utf8'
                . ';port=' . DB_PORT;

            $pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        }
    }
?>