<?php 
    require_once __DIR__ . '/../Database.php';

    /**
     * Classe Referentiel
     * Gère la récupération des données de référentiel pour alimenter les listes
     * déroulantes de filtres (types de prise, moyens de paiement, aménageurs,
     * départements et années de mise en service).
    */
    class Referentiel {
        /**
         * @var PDO Instance de connexion à la base de données.
        */
        private PDO $db;

        /**
         * Constructeur du référentiel.
         * 
         * @param PDO $db Connexion active à la base de données injectée.
        */
        public function __construct(PDO $db) {
            $this->db = $db;
        }

        /**
         * Récupère la liste complète des types de prise présents en base, ordonnée.
         * 
         * @return array Liste des types de prise (chaque élément étant un tableau associatif).
        */
        public function getTypesPrise(): array {
            try {
                $stmt = $this->db->prepare('SELECT type_prise FROM prise ORDER BY type_prise');
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $exception) {
                error_log('Referentiel error: ' . $exception->getMessage());
                return [];
            }
        }

        /**
         * Récupère la liste complète des types de paiement présents en base, ordonnée.
         * 
         * @return array Liste des types de paiement.
        */
        public function getTypesPaiement(): array {
            try {
                $stmt = $this->db->prepare('SELECT type_paiement FROM paiement ORDER BY type_paiement');
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $exception) {
                error_log('Referentiel error: ' . $exception->getMessage());
                return [];
            }
        }

        /**
         * Récupère 20 aménageurs distincts au hasard dans la base de données.
         * Conforme à l'exigence du cahier des charges minimal d'aléatoire et de limite.
         * 
         * @return array Liste des noms d'aménageurs distincts.
        */
        public function getAmenageurs(): array {
            try {
                $stmt = $this->db->prepare("SELECT DISTINCT nom_acteur FROM acteur WHERE role_acteur = 'Aménageur' ORDER BY RAND() LIMIT 20");
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $exception) {
                error_log('Referentiel error: ' . $exception->getMessage());
                return [];
            }
        }

        /**
         * Récupère la liste des départements enregistrés (code et nom).
         * 
         * @return array Liste des départements bretons.
        */
        public function getDepartements(): array {
            try {
                $stmt = $this->db->prepare('SELECT code_dep, nom_departement FROM departement');
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $exception) {
                error_log('Referentiel error: ' . $exception->getMessage());
                return [];
            }
        }

        /**
         * Récupère les années uniques de mise en service des stations, ordonnées.
         * 
         * @return array Liste des années d'installation distinctes.
        */
        public function getAnneeMiseEnService(): array {
            try {
                $stmt = $this->db->prepare('SELECT DISTINCT YEAR(date_mise_en_service) AS annee FROM station ORDER BY annee ASC;');
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $exception) {
                error_log('Referentiel error: ' . $exception->getMessage());
                return [];
            }
        }
    }
?>