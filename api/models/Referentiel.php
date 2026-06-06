<?php 
    require_once __DIR__ . '/../Database.php';

    class Referentiel {
        private PDO $db;

        public function __construct(PDO $db) {
            $this->db = $db;
        }

        /**
         * Retourne la liste des types de prise.
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
         * Retourne la liste des types de paiement.
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