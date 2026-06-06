<?php 
    require_once __DIR__ . '/../Database.php';

    class Stats {
        private PDO $db;

        public function __construct(PDO $db) {
            $this->db = $db;
        }

        public function getNbrPDC(): int {
            try {
                $stmt = $this->db->prepare('SELECT COUNT(*) AS total_pdc FROM point_de_charge;');
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row ? (int)$row['total_pdc'] : 0;
            } catch (PDOException $exception) {
                error_log('Stats error: ' . $exception->getMessage());
                return -1;
            }
        }
        
        /**
         * Retourne le nombre de points de charge par années.
        */
        public function getNbrPDCParAnnees(): array {
            try {
                $stmt = $this->db->prepare('
                    SELECT 
                        YEAR(s.date_mise_en_service) AS annee,
                        COUNT(pd.id_pdc) AS nombre_points_de_charge
                    FROM 
                        station s
                    INNER JOIN 
                        possede_des pd ON s.id_station_itinerance = pd.id_station_itinerance
                    GROUP BY 
                        YEAR(s.date_mise_en_service)
                    ORDER BY 
                        annee ASC;
                ');
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $exception) {
                error_log('Stats error: ' . $exception->getMessage());
                return [];
            } 
        }

        /**
         * Retourne le nombre de points de charge par departements.
        */
        public function getNbrPDCParDepartements(): array {
            try {
                $stmt = $this->db->prepare('
                    SELECT 
                        dep.code_dep AS numero_departement,
                        dep.nom_departement AS nom_departement,
                        COUNT(pdc.id_pdc) AS nombre_points_de_charge
                    FROM point_de_charge pdc
                    INNER JOIN possede_des pd ON pdc.id_pdc = pd.id_pdc
                    INNER JOIN station s ON pd.id_station_itinerance = s.id_station_itinerance
                    INNER JOIN commune c ON s.code_insee_commune = c.code_insee_commune
                    INNER JOIN departement dep ON c.code_dep = dep.code_dep
                    GROUP BY 
                        dep.code_dep,
                        dep.nom_departement
                    ORDER BY 
                        nombre_points_de_charge DESC;
                ');
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $exception) {
                error_log('Stats error: ' . $exception->getMessage());
                return [];
            }
        }

        public function getNbrPDCDepartementAnnees(): array {
            try {
                $stmt = $this->db->prepare('
                    SELECT 
                        dep.code_dep AS numero_departement,
                        dep.nom_departement AS nom_departement,
                        YEAR(s.date_mise_en_service) AS annee,
                        COUNT(pdc.id_pdc) AS nombre_points_de_charge
                    FROM point_de_charge pdc
                    INNER JOIN possede_des pd ON pdc.id_pdc = pd.id_pdc
                    INNER JOIN station s ON pd.id_station_itinerance = s.id_station_itinerance
                    INNER JOIN commune c ON s.code_insee_commune = c.code_insee_commune
                    INNER JOIN departement dep ON c.code_dep = dep.code_dep
                    GROUP BY 
                        dep.code_dep,
                        dep.nom_departement,
                        YEAR(s.date_mise_en_service)
                    ORDER BY 
                        annee ASC;
                ');
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $exception) {
                error_log('Stats error: ' . $exception->getMessage());
                return [];
            }
        }

        /**
         * Retourne le nombre d'amenageurs.
        */
        public function getNbrAmenageurs(): int {
            try {
                $stmt = $this->db->prepare('
                    SELECT COUNT(*) AS nbr_amenageur
                    FROM acteur
                    WHERE role_acteur = \'Aménageur\';
                ');
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row ? (int)$row['nbr_amenageur'] : 0;
            } catch (PDOException $exception) {
                error_log('Stats error: ' . $exception->getMessage());
                return -1;
            }
        }

        /**
         * Retourne le nombre de types de prises.
        */
        public function getNbrTypeDePrises(): int {
            try {
                $stmt = $this->db->prepare('
                    SELECT COUNT(*) AS nbr_type_prise 
                    FROM prise;
                ');
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row ? (int)$row['nbr_type_prise'] : 0;
            } catch (PDOException $exception) {
                error_log('Stats error: ' . $exception->getMessage());
                return -1;
            }
        }
    }
?>