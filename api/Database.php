<?php 
    require_once __DIR__ . '/constants.php';

    /**
     * Classe Database
     * Gère l'établissement et la configuration de la connexion à la base de données
     * MySQL/MariaDB à l'aide de l'API PDO.
    */
    class Database {
        /**
         * Établit et retourne une nouvelle connexion PDO configurée.
         * Les paramètres de connexion sont définis par les constantes du fichier constants.php.
         * Active le mode de rapport d'erreur d'exception (ERRMODE_EXCEPTION) pour la sécurité.
         * 
         * @return PDO Connexion PDO prête à l'emploi.
         * @throws PDOException Si la tentative de connexion échoue.
        */
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
