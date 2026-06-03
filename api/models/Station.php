<?php
require_once __DIR__ . '/../Database.php';

class Station {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupère une station par son identifiant itinérance.
     */
    public function getByItinerance(string $id): ?array {
        try {
            // 1. Récupération des infos de la station de base
            $stmt = $this->db->prepare('SELECT * FROM station WHERE id_station_itinerance = :id');
            $stmt->execute([':id' => $id]);
            $station = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$station) {
                return null;
            }

            // Initialisation des clés optionnelles à null
            $station['nom_commune'] = null;
            $station['nom_departement'] = null;
            $station['amenageur'] = null;
            $station['operateur'] = null;

            // 2. Commune & Département
            if (!empty($station['code_insee_commune'])) {
                $stmtC = $this->db->prepare('SELECT nom_commune, code_dep FROM commune WHERE code_insee_commune = :code_insee');
                $stmtC->execute([':code_insee' => $station['code_insee_commune']]);
                $commune = $stmtC->fetch(PDO::FETCH_ASSOC);
                if ($commune) {
                    $station['nom_commune'] = $commune['nom_commune'];

                    if (!empty($commune['code_dep'])) {
                        $stmtD = $this->db->prepare('SELECT nom_departement FROM departement WHERE code_dep = :code_dep');
                        $stmtD->execute([':code_dep' => $commune['code_dep']]);
                        $dep = $stmtD->fetch(PDO::FETCH_ASSOC);
                        if ($dep) {
                            $station['nom_departement'] = $dep['nom_departement'];
                        }
                    }
                }
            }

            // 3. Acteur aménageur
            if (!empty($station['id_acteur'])) {
                $stmtA = $this->db->prepare('SELECT nom_acteur FROM acteur WHERE id_acteur = :id_acteur');
                $stmtA->execute([':id_acteur' => $station['id_acteur']]);
                $act = $stmtA->fetch(PDO::FETCH_ASSOC);
                if ($act) {
                    $station['amenageur'] = $act['nom_acteur'];
                }
            }

            // 4. Acteur opérateur
            if (!empty($station['id_acteur_est_utiliser_par'])) {
                $stmtA2 = $this->db->prepare('SELECT nom_acteur FROM acteur WHERE id_acteur = :id_acteur');
                $stmtA2->execute([':id_acteur' => $station['id_acteur_est_utiliser_par']]);
                $act2 = $stmtA2->fetch(PDO::FETCH_ASSOC);
                if ($act2) {
                    $station['operateur'] = $act2['nom_acteur'];
                }
            }

            return $station;
        } catch (PDOException $exception) {
            error_log('Station getByItinerance error: ' . $exception->getMessage());
            return null;
        }
    }
}
?>
