<?php
require_once __DIR__ . '/constants.php';

class Database {
    private static ?Database $instance = null;
    private PDO $pdo;

    /**
     * Constructeur privé — empêche l'instanciation directe.
     * Crée la connexion PDO avec les constantes de constants.php.
     */
    private function __construct() {
        $dsn = 'mysql:host=' . DB_SERVER
             . ';dbname=' . DB_NAME
             . ';charset=utf8'
             . ';port=' . DB_PORT;

        $this->pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Retourne l'instance unique de Database (singleton).
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Retourne l'objet PDO pour exécuter des requêtes.
     */
    public function getConnection(): PDO {
        return $this->pdo;
    }
}
?>