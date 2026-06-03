<?php require_once __DIR__ . '/../Database.php';

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

    /**
     * Retourne la liste des types d'implantation.
     */
    public function getImplantations(): array {
        try {
            $stmt = $this->db->prepare('SELECT implantation_station FROM implantation ORDER BY implantation_station');
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            error_log('Referentiel error: ' . $exception->getMessage());
            return [];
        }
    }

    /**
     * Retourne la liste des enseignes.
     */
    public function getEnseignes(): array {
        try {
            $stmt = $this->db->prepare('SELECT nom_enseigne FROM enseigne ORDER BY nom_enseigne');
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            error_log('Referentiel error: ' . $exception->getMessage());
            return [];
        }
    }
}
?>
