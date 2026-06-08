<?php 
    require_once __DIR__ . '/../Database.php';

    /**
     * Classe PointDeCharge
     * Modèle principal représentant un point de charge (PDC) pour véhicule électrique.
     * Gère le décompte, la recherche paginée, la récupération unitaire (fiche détails),
     * la création complète sécurisée en transaction, la modification, la récupération cartographique
     * (avec agrégation de stations selon le niveau de zoom) et la suppression.
    */
    class PointDeCharge {
        /**
         * @var PDO Instance de connexion à la base de données.
        */
        private PDO $db;

        /**
         * Constructeur de la classe PointDeCharge.
         * 
         * @param PDO $db Connexion active à la base de données injectée.
        */
        public function __construct(PDO $db) {
            $this->db = $db;
        }

        /**
         * Compte le nombre total de points de charge physiques enregistrés dans la table point_de_charge.
         * 
         * @return int Nombre total de points de charge, ou 0 en cas d'erreur.
        */
        public function count(): int {
            try {
                $request = '
                    SELECT COUNT(*) AS total
                    FROM point_de_charge
                ';
                $statement = $this->db->prepare($request);
                $statement->execute();
                $row = $statement->fetch(PDO::FETCH_ASSOC);
                return (int)$row['total'];
            } catch (PDOException $exception) {
                error_log('Count error: ' . $exception->getMessage());
                return 0;
            }
        }

        /**
         * Récupère la liste complète des points de charge physiques avec leurs types de prise associés.
         * Charge également en lot les relations externes correspondantes (station, commune, département, acteurs).
         * 
         * @param bool $accueil Si vrai, retourne 5 bornes aléatoires pour l'aperçu de la page d'accueil d'administration.
         * @param int|null $limit Nombre maximum de résultats à retourner (pour la pagination).
         * @param int $offset Indice du premier élément à retourner (pour la pagination).
         * @return array Liste des points de charge avec leurs relations résolues.
        */
        public function getAll(bool $accueil = false, ?int $limit = null, int $offset = 0): array {
            try {
                // 1. Récupération de la liste de base (PDC + type de prise)
                $request = '
                    SELECT pdc.id_pdc, pdc.tarification, pdc.puissance, GROUP_CONCAT(ad.type_prise SEPARATOR ", ") AS type_prise
                    FROM point_de_charge pdc
                    LEFT JOIN a_des ad ON pdc.id_pdc = ad.id_pdc
                ';

                if ($accueil) {
                    $request .= ' GROUP BY pdc.id_pdc ORDER BY RAND() LIMIT 6';
                } else {
                    $request .= ' GROUP BY pdc.id_pdc';
                    if ($limit !== null) {
                        $request .= ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
                    }
                }

                $statement = $this->db->prepare($request);
                $statement->execute();
                $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

                return $this->loadRelationsForRows($rows);
            } catch (PDOException $exception) {
                error_log('Request error: ' . $exception->getMessage());
                return [];
            }
        }

        /**
         * Effectue une recherche multicritères filtrée des points de charge.
         * Supporte le filtrage optionnel par nom d'aménageur, type de prise et code département.
         * 
         * @param array $filters Tableau de critères de filtrage ('amenageur', 'type_prise', 'code_dep').
         * @param int|null $limit Nombre maximum de lignes à retourner (pour la pagination).
         * @param int $offset Indice de décalage (pour la pagination).
         * @return array Liste filtrée des points de charge correspondants.
        */
        public function search(array $filters, ?int $limit = null, int $offset = 0): array {
            try {
                $request = '
                    SELECT pdc.id_pdc, pdc.tarification, pdc.puissance, GROUP_CONCAT(ad.type_prise SEPARATOR ", ") AS type_prise
                    FROM point_de_charge pdc
                    LEFT JOIN a_des ad ON pdc.id_pdc = ad.id_pdc
                    LEFT JOIN possede_des pd ON pdc.id_pdc = pd.id_pdc
                    LEFT JOIN station s ON pd.id_station_itinerance = s.id_station_itinerance
                    LEFT JOIN acteur a ON s.id_acteur = a.id_acteur
                    LEFT JOIN commune c ON s.code_insee_commune = c.code_insee_commune
                ';

                $whereClauses = [];
                $queryParams = [];

                if (!empty($filters['amenageur'])) {
                    $whereClauses[] = 'a.nom_acteur = :amenageur';
                    $queryParams[':amenageur'] = $filters['amenageur'];
                }
                if (!empty($filters['type_prise'])) {
                    $whereClauses[] = 'ad.type_prise = :type_prise';
                    $queryParams[':type_prise'] = $filters['type_prise'];
                }
                if (!empty($filters['code_dep'])) {
                    $whereClauses[] = 'c.code_dep = :code_dep';
                    $queryParams[':code_dep'] = $filters['code_dep'];
                }

                if (!empty($whereClauses)) {
                    $request .= ' WHERE ' . implode(' AND ', $whereClauses);
                }

                $request .= ' GROUP BY pdc.id_pdc';

                if ($limit !== null) {
                    $request .= ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
                }

                $statement = $this->db->prepare($request);
                $statement->execute($queryParams);
                $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

                return $this->loadRelationsForRows($rows);
            } catch (PDOException $exception) {
                error_log('Search error: ' . $exception->getMessage());
                return [];
            }
        }

        /**
         * Compte le nombre total de points de charge uniques correspondant aux critères de filtrage.
         * Utilisé pour la pagination de la page de recherche.
         * 
         * @param array $filters Critères de filtrage ('amenageur', 'type_prise', 'code_dep').
         * @return int Nombre de points de charge correspondants.
        */
        public function searchCount(array $filters): int {
            try {
                $request = '
                    SELECT COUNT(DISTINCT pdc.id_pdc) AS total
                    FROM point_de_charge pdc
                    LEFT JOIN a_des ad ON pdc.id_pdc = ad.id_pdc
                    LEFT JOIN possede_des pd ON pdc.id_pdc = pd.id_pdc
                    LEFT JOIN station s ON pd.id_station_itinerance = s.id_station_itinerance
                    LEFT JOIN acteur a ON s.id_acteur = a.id_acteur
                    LEFT JOIN commune c ON s.code_insee_commune = c.code_insee_commune
                ';

                $whereClauses = [];
                $queryParams = [];

                if (!empty($filters['amenageur'])) {
                    $whereClauses[] = 'a.nom_acteur = :amenageur';
                    $queryParams[':amenageur'] = $filters['amenageur'];
                }
                if (!empty($filters['type_prise'])) {
                    $whereClauses[] = 'ad.type_prise = :type_prise';
                    $queryParams[':type_prise'] = $filters['type_prise'];
                }
                if (!empty($filters['code_dep'])) {
                    $whereClauses[] = 'c.code_dep = :code_dep';
                    $queryParams[':code_dep'] = $filters['code_dep'];
                }

                if (!empty($whereClauses)) {
                    $request .= ' WHERE ' . implode(' AND ', $whereClauses);
                }

                $statement = $this->db->prepare($request);
                $statement->execute($queryParams);
                $row = $statement->fetch(PDO::FETCH_ASSOC);
                return (int) ($row['total'] ?? 0);
            } catch (PDOException $exception) {
                error_log('Search count error: ' . $exception->getMessage());
                return 0;
            }
        }

        /**
         * Chargeur de relations en lot (Eager Loading manuel).
         * Regroupe en lot les requêtes vers la station, la commune et les acteurs
         * afin d'optimiser les performances de l'application et prévenir le problème N+1.
         * 
         * @param array $rows Liste brute de lignes de points de charge.
         * @return array Liste des points de charge enrichie de leurs relations.
        */
        private function loadRelationsForRows(array $rows): array {
            if (empty($rows)) {
                return [];
            }

            // Extraction des IDs de points de charge uniques pour charger les relations en lot
            $pdcIds = array_filter(array_unique(array_column($rows, 'id_pdc')));
            if (empty($pdcIds)) {
                return array_map(function($row) {
                    return [
                        'nom_station' => null,
                        'id_pdc' => $row['id_pdc'],
                        'amenageur' => null,
                        'operateur' => null,
                        'type_prise' => $row['type_prise'],
                        'commune' => null,
                        'code_dep' => null,
                        'tarification' => $row['tarification'],
                        'puissance' => isset($row['puissance']) ? (float)$row['puissance'] : null,
                        'date_mise_en_service' => null
                    ];
                }, $rows);
            }

            // 2. Récupération des infos des stations liées à ces PDC
            $placeholders = implode(',', array_fill(0, count($pdcIds), '?'));
            $stationQuery = "
                SELECT pd.id_pdc, s.nom_station, s.id_acteur, s.id_acteur_est_utiliser_par, s.code_insee_commune, s.date_mise_en_service
                FROM possede_des pd
                JOIN station s ON pd.id_station_itinerance = s.id_station_itinerance
                WHERE pd.id_pdc IN ($placeholders)
            ";
            $stmtS = $this->db->prepare($stationQuery);
            $stmtS->execute(array_values($pdcIds));
            $stationRows = $stmtS->fetchAll(PDO::FETCH_ASSOC);

            $stationsByPdc = [];
            $actorIds = [];
            $communeCodes = [];
            foreach ($stationRows as $sRow) {
                $stationsByPdc[$sRow['id_pdc']] = $sRow;
                if (!empty($sRow['id_acteur'])) {
                    $actorIds[] = $sRow['id_acteur'];
                }
                if (!empty($sRow['id_acteur_est_utiliser_par'])) {
                    $actorIds[] = $sRow['id_acteur_est_utiliser_par'];
                }
                if (!empty($sRow['code_insee_commune'])) {
                    $communeCodes[] = $sRow['code_insee_commune'];
                }
            }

            // 3. Récupération des noms des acteurs en lot
            $actorsById = [];
            if (!empty($actorIds)) {
                $actorIds = array_unique($actorIds);
                $actorPlaceholders = implode(',', array_fill(0, count($actorIds), '?'));
                $actorQuery = "SELECT id_acteur, nom_acteur FROM acteur WHERE id_acteur IN ($actorPlaceholders)";
                $stmtA = $this->db->prepare($actorQuery);
                $stmtA->execute(array_values($actorIds));
                $actorRows = $stmtA->fetchAll(PDO::FETCH_ASSOC);
                foreach ($actorRows as $aRow) {
                    $actorsById[$aRow['id_acteur']] = $aRow['nom_acteur'];
                }
            }

            // 4. Récupération des noms de communes en lot
            $communesByInsee = [];
            if (!empty($communeCodes)) {
                $communeCodes = array_unique($communeCodes);
                $communePlaceholders = implode(',', array_fill(0, count($communeCodes), '?'));
                $communeQuery = "SELECT code_insee_commune, nom_commune, code_dep FROM commune WHERE code_insee_commune IN ($communePlaceholders)";
                $stmtC = $this->db->prepare($communeQuery);
                $stmtC->execute(array_values($communeCodes));
                $communeRows = $stmtC->fetchAll(PDO::FETCH_ASSOC);
                foreach ($communeRows as $cRow) {
                    $communesByInsee[$cRow['code_insee_commune']] = [
                        'nom_commune' => $cRow['nom_commune'],
                        'code_dep' => $cRow['code_dep']
                    ];
                }
            }

            // 5. Assemblage final en mémoire
            $results = [];
            foreach ($rows as $row) {
                $idPdc = $row['id_pdc'];
                $station = $stationsByPdc[$idPdc] ?? null;

                $nomStation = $station ? $station['nom_station'] : null;
                $idAmenageur = $station ? $station['id_acteur'] : null;
                $idOperateur = $station ? $station['id_acteur_est_utiliser_par'] : null;
                $codeCommune = $station ? $station['code_insee_commune'] : null;
                $dateMiseEnService = $station ? $station['date_mise_en_service'] : null;

                $communeInfo = ($codeCommune !== null && isset($communesByInsee[$codeCommune])) ? $communesByInsee[$codeCommune] : null;

                $results[] = [
                    'nom_station' => $nomStation,
                    'id_pdc' => $idPdc,
                    'amenageur' => $idAmenageur !== null ? ($actorsById[$idAmenageur] ?? null) : null,
                    'operateur' => $idOperateur !== null ? ($actorsById[$idOperateur] ?? null) : null,
                    'type_prise' => $row['type_prise'],
                    'commune' => $communeInfo ? $communeInfo['nom_commune'] : null,
                    'code_dep' => $communeInfo ? $communeInfo['code_dep'] : null,
                    'tarification' => $row['tarification'],
                    'puissance' => isset($row['puissance']) ? (float)$row['puissance'] : null,
                    'date_mise_en_service' => $dateMiseEnService
                ];
            }

            return $results;
        }

        /**
         * Récupère la fiche détaillée d'un point de charge spécifique par son ID.
         * Résout l'ensemble des relations connexes de station, de commune et de modes de paiement.
         * 
         * @param int $id_pdc L'identifiant unique du point de charge.
         * @return array|null Fiche détaillée du point de charge, ou null s'il est introuvable.
        */
        public function getById(int $id_pdc): ?array {
            try {
                // 1. Récupération des infos de base du PDC et type de prise
                $request = '
                    SELECT pdc.*, GROUP_CONCAT(ad.type_prise SEPARATOR ", ") AS type_prise
                    FROM point_de_charge pdc
                    LEFT JOIN a_des ad ON pdc.id_pdc = ad.id_pdc
                    WHERE pdc.id_pdc = :id_pdc
                    GROUP BY pdc.id_pdc
                ';
                $params = [':id_pdc' => $id_pdc];

                $statement = $this->db->prepare($request);
                $statement->execute($params);
                $pdc = $statement->fetch(PDO::FETCH_ASSOC);

                if (!$pdc) {
                    return null;
                }

                // Initialisation de la structure de retour attendue par les vues
                $res = [
                    'id_pdc' => (int) $pdc['id_pdc'],
                    'type_prise' => $pdc['type_prise'] ?? 'Non renseigné',
                    'puissance' => $pdc['puissance'] !== null ? (float)$pdc['puissance'] : null,
                    'cable_t2_attache' => (int)($pdc['cable_t2_attache'] ?? 0),
                    'latitude' => $pdc['lat'] !== null ? (float)$pdc['lat'] : null,
                    'longitude' => $pdc['lon'] !== null ? (float)$pdc['lon'] : null,
                    'tarification' => $pdc['tarification'],
                    'condition_acces' => $pdc['pdc_condition'],
                    'gratuit' => (int)($pdc['gratuit'] ?? 0),
                    'nom_station' => null,
                    'adresse_station' => null,
                    'amenageur' => null,
                    'siren_amenageur' => null,
                    'operateur' => null,
                    'contact_operateur' => null,
                    'commune' => null,
                    'departement' => null,
                    'types_paiement' => 'Aucun moyen spécifié'
                ];

                // 2. Récupération de la station associée
                $stmtS = $this->db->prepare('
                    SELECT s.nom_station, s.id_acteur, s.id_acteur_est_utiliser_par, s.code_insee_commune, s.adresse_station
                    FROM possede_des pd
                    JOIN station s ON pd.id_station_itinerance = s.id_station_itinerance
                    WHERE pd.id_pdc = :id_pdc
                    LIMIT 1
                ');
                $stmtS->execute([':id_pdc' => $id_pdc]);
                $station = $stmtS->fetch(PDO::FETCH_ASSOC);

                if ($station) {
                    $res['nom_station'] = $station['nom_station'];
                    $res['adresse_station'] = $station['adresse_station'];

                    // 2.1 Acteur aménageur
                    if (!empty($station['id_acteur'])) {
                        $stmtA = $this->db->prepare('SELECT nom_acteur, siren_acteur FROM acteur WHERE id_acteur = :id');
                        $stmtA->execute([':id' => $station['id_acteur']]);
                        $acteurAm = $stmtA->fetch(PDO::FETCH_ASSOC);
                        if ($acteurAm) {
                            $res['amenageur'] = $acteurAm['nom_acteur'];
                            $res['siren_amenageur'] = $acteurAm['siren_acteur'];
                        }
                    }

                    // 2.2 Acteur opérateur
                    if (!empty($station['id_acteur_est_utiliser_par'])) {
                        $stmtO = $this->db->prepare('SELECT nom_acteur, contact_acteur FROM acteur WHERE id_acteur = :id');
                        $stmtO->execute([':id' => $station['id_acteur_est_utiliser_par']]);
                        $acteurOp = $stmtO->fetch(PDO::FETCH_ASSOC);
                        if ($acteurOp) {
                            $res['operateur'] = $acteurOp['nom_acteur'];
                            $res['contact_operateur'] = $acteurOp['contact_acteur'];
                        }
                    }

                    // 2.3 Commune & Département
                    if (!empty($station['code_insee_commune'])) {
                        $stmtC = $this->db->prepare('SELECT nom_commune, code_dep FROM commune WHERE code_insee_commune = :code_insee');
                        $stmtC->execute([':code_insee' => $station['code_insee_commune']]);
                        $commune = $stmtC->fetch(PDO::FETCH_ASSOC);
                        if ($commune) {
                            $res['commune'] = $commune['nom_commune'];

                            if (!empty($commune['code_dep'])) {
                                $stmtD = $this->db->prepare('SELECT nom_departement FROM departement WHERE code_dep = :code_dep');
                                $stmtD->execute([':code_dep' => $commune['code_dep']]);
                                $dep = $stmtD->fetch(PDO::FETCH_ASSOC);
                                if ($dep) {
                                    $res['departement'] = $dep['nom_departement'];
                                }
                            }
                        }
                    }
                }

                // 3. Récupération des modes de paiement
                $stmtP = $this->db->prepare('SELECT type_paiement FROM est_payer_avec WHERE id_pdc = :id_pdc');
                $stmtP->execute([':id_pdc' => $id_pdc]);
                $payments = $stmtP->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($payments)) {
                    $res['types_paiement'] = implode(', ', $payments);
                }

                return $res;
            } catch (PDOException $exception) {
                error_log('Request error: ' . $exception->getMessage());
                return null;
            }
        }

        /**
         * Met à jour les caractéristiques modifiables d'un point de charge.
         * 
         * @param array $data Tableau associatif contenant les nouveaux paramètres ('id_pdc', 'puissance', 'cable_t2_attache', 'latitude', 'longitude', 'tarification').
         * @return bool Vrai en cas de réussite de la mise à jour, faux en cas d'erreur.
        */
        public function update(array $data): bool {
            try {
                $stmt = $this->db->prepare('
                    UPDATE point_de_charge
                    SET
                        puissance = :puissance,
                        cable_t2_attache = :cable_t2_attache,
                        lat = :latitude,
                        lon = :longitude,
                        tarification = :tarification
                    WHERE id_pdc = :id_pdc
                ');
                $stmt->execute([
                    ':puissance' => $data['puissance'] ?? null,
                    ':cable_t2_attache' => $data['cable_t2_attache'] ?? 0,
                    ':latitude' => $data['latitude'] ?? null,
                    ':longitude' => $data['longitude'] ?? null,
                    ':tarification' => $data['tarification'] ?? null,
                    ':id_pdc' => $data['id_pdc'],
                ]);

                return true;
            } catch (PDOException $exception) {
                error_log('Update error: ' . $exception->getMessage());
                return false;
            }
        }

        /**
         * Insère un point de charge complet avec sa station et ses relations au sein d'une transaction SQL unifiée.
         * Insère ou réutilise le département et la commune correspondants, crée les acteurs nécessaires (aménageur, opérateur),
         * instancie la station physique puis la borne de recharge en lui assignant ses relations (type de prise, modes de paiement).
         * Effectue un rollback complet des insertions partielles en cas d'erreur de base de données.
         * 
         * @param array $data Tableau associatif contenant l'intégralité des champs de saisie du formulaire d'ajout.
         * @return int|false L'identifiant généré du nouveau point de charge inséré, ou false en cas d'échec.
        */
        public function create(array $data): int|false {
            try {
                $this->db->beginTransaction();

                // 1. Département (INSERT IGNORE : peut déjà exister)
                $this->db->prepare('INSERT IGNORE INTO departement (code_dep, nom_departement) VALUES (:code_dep, :nom_dep)')
                ->execute([':code_dep' => $data['code_dep'], ':nom_dep' => $data['nom_departement']]);

                // 2. Commune (INSERT IGNORE : peut déjà exister)
                $this->db->prepare('INSERT IGNORE INTO commune (code_insee_commune, nom_commune, code_dep) VALUES (:code_insee, :nom_commune, :code_dep)')
                ->execute([':code_insee' => $data['code_insee'], ':nom_commune' => $data['nom_commune'], ':code_dep' => $data['code_dep']]);

                // 3. Acteur aménageur
                $stmtAm = $this->db->prepare('INSERT INTO acteur (siren_acteur, nom_acteur, contact_acteur, role_acteur) VALUES (:siren, :nom, :contact, :role)');
                $stmtAm->execute([':siren' => $data['siren_amenageur'], ':nom' => $data['nom_amenageur'], ':contact' => $data['contact_amenageur'] ?? null, ':role' => 'amenageur']);
                $id_acteur_am = $this->db->lastInsertId();

                // 4. Acteur opérateur
                $stmtOp = $this->db->prepare('INSERT INTO acteur (siren_acteur, nom_acteur, contact_acteur, telephone_acteur, role_acteur) VALUES (:siren, :nom, :contact, :telephone, :role)');
                $stmtOp->execute([':siren' => 0, ':nom' => $data['nom_operateur'], ':contact' => $data['contact_operateur'] ?? null, ':telephone' => $data['telephone_operateur'] ?? null, ':role' => 'operateur']);
                $id_acteur_op = $this->db->lastInsertId();

                // 5. ID station : généré si non fourni
                $id_station = !empty($data['id_station_itinerance']) ? $data['id_station_itinerance'] : 'STA_' . time() . '_' . rand(100, 999);

                // 6. Station
                $this->db->prepare('
                    INSERT INTO station (id_station_itinerance, nom_station, adresse_station, nbr_pdc, date_mise_en_service, code_insee_commune, id_acteur, id_acteur_est_utiliser_par)
                    VALUES (:id_sta, :nom, :adresse, 1, :date_service, :code_insee, :id_am, :id_op)
                ')->execute([
                    ':id_sta' => $id_station,
                    ':nom' => $data['nom_station'],
                    ':adresse' => $data['adresse_station'],
                    ':date_service' => $data['date_service'] ?: null,
                    ':code_insee' => $data['code_insee'],
                    ':id_am' => $id_acteur_am,
                    ':id_op' => $id_acteur_op,
                ]);

                // 7. id_pdc : MAX + 1
                $row = $this->db->query('SELECT COALESCE(MAX(id_pdc), 0) + 1 AS next_id FROM point_de_charge')->fetch(PDO::FETCH_ASSOC);
                $id_pdc = (int) $row['next_id'];

                // 8. Point de charge
                $this->db->prepare('
                    INSERT INTO point_de_charge (id_pdc, lon, lat, puissance, cable_t2_attache, gratuit, tarification)
                    VALUES (:id_pdc, :lon, :lat, :puissance, :cable, :gratuit, :tarification)
                ')->execute([
                    ':id_pdc' => $id_pdc,
                    ':lon' => $data['longitude'],
                    ':lat' => $data['latitude'],
                    ':puissance' => $data['puissance'],
                    ':cable' => (int)($data['cable_t2_attache'] ?? 0),
                    ':gratuit' => (int)($data['gratuit'] ?? 0),
                    ':tarification' => $data['tarification'] ?: null,
                ]);

                // 9. Relation pdc ↔ type de prise
                if (!empty($data['types_prises']) && is_array($data['types_prises'])) {
                    $stmtPrise = $this->db->prepare('INSERT IGNORE INTO a_des (id_pdc, type_prise) VALUES (:id_pdc, :type_prise)');
                    foreach ($data['types_prises'] as $prise) {
                        $stmtPrise->execute([':id_pdc' => $id_pdc, ':type_prise' => $prise]);
                    }
                } else if (!empty($data['type_prise'])) {
                    $this->db->prepare('INSERT INTO a_des (id_pdc, type_prise) VALUES (:id_pdc, :type_prise)')
                    ->execute([':id_pdc' => $id_pdc, ':type_prise' => $data['type_prise']]);
                }

                // 10. Relation station ↔ pdc
                $this->db->prepare('INSERT INTO possede_des (id_pdc, id_station_itinerance) VALUES (:id_pdc, :id_sta)')
                ->execute([':id_pdc' => $id_pdc, ':id_sta' => $id_station]);

                // 11. Types de paiement (0 ou plusieurs)
                if (!empty($data['types_paiement'])) {
                    $stmtPay = $this->db->prepare('INSERT IGNORE INTO est_payer_avec (type_paiement, id_pdc) VALUES (:type, :id_pdc)');
                    foreach ($data['types_paiement'] as $type) {
                        $stmtPay->execute([':type' => $type, ':id_pdc' => $id_pdc]);
                    }
                }

                $this->db->commit();
                return $id_pdc;
            } catch (PDOException $exception) {
                $this->db->rollBack();
                error_log('Insert error: ' . $exception->getMessage());
                return false;
            }
        }

        /**
         * Récupère les données géolocalisées optimisées pour l'affichage cartographique.
         * Si le niveau de zoom est faible (zoom < 10), agrège les bornes physiques par station pour économiser la mémoire du navigateur.
         * Si le zoom est élevé (zoom >= 10), renvoie les points de charge individuels situés dans la boîte englobante (Bounding Box) visible.
         * 
         * @param array $filters Critères de filtrage et limites de coordonnées.
         * @param string|int [filters.annee] Année de mise en service filtrée.
         * @param string|int [filters.code_dep] Code de département breton filtré.
         * @param int [filters.zoom] Niveau de zoom actuel de l'utilisateur.
         * @param float [filters.min_lat] Latitude minimale de la Bounding Box.
         * @param float [filters.max_lat] Latitude maximale de la Bounding Box.
         * @param float [filters.min_lng] Longitude minimale de la Bounding Box.
         * @param float [filters.max_lng] Longitude maximale de la Bounding Box.
         * @return array Tableau de marqueurs géolocalisés.
        */
        public function getMapPoints(array $filters): array {
            try {
                $zoom = isset($filters['zoom']) ? (int)$filters['zoom'] : 0;
                
                // Si le zoom est faible (zoom < 10), on groupe par station pour limiter le nombre de marqueurs
                if ($zoom > 0 && $zoom < 10) {
                    $request = '
                        SELECT 
                            MIN(pdc.id_pdc) AS id, 
                            s.nom_station,
                            s.adresse_station,
                            c.nom_commune AS localite, 
                            c.code_dep AS dept, 
                            YEAR(s.date_mise_en_service) AS annee, 
                            AVG(pdc.puissance) AS puissance, 
                            AVG(pdc.lat) AS lat, 
                            AVG(pdc.lon) AS lng,
                            GROUP_CONCAT(DISTINCT ad.type_prise SEPARATOR ", ") AS type_prise,
                            COUNT(pdc.id_pdc) AS count_pdc
                        FROM point_de_charge pdc
                        JOIN possede_des pd ON pdc.id_pdc = pd.id_pdc
                        JOIN station s ON pd.id_station_itinerance = s.id_station_itinerance
                        JOIN commune c ON s.code_insee_commune = c.code_insee_commune
                        LEFT JOIN a_des ad ON pdc.id_pdc = ad.id_pdc
                        WHERE pdc.lat IS NOT NULL AND pdc.lon IS NOT NULL
                    ';
                } else {
                    // Sinon (zoom >= 10 ou zoom non spécifié), on affiche les points de charge individuels
                    $request = '
                        SELECT 
                            pdc.id_pdc AS id, 
                            s.nom_station,
                            s.adresse_station,
                            c.nom_commune AS localite, 
                            c.code_dep AS dept, 
                            YEAR(s.date_mise_en_service) AS annee, 
                            pdc.puissance, 
                            pdc.lat, 
                            pdc.lon AS lng,
                            ad.type_prise,
                            1 AS count_pdc
                        FROM point_de_charge pdc
                        JOIN possede_des pd ON pdc.id_pdc = pd.id_pdc
                        JOIN station s ON pd.id_station_itinerance = s.id_station_itinerance
                        JOIN commune c ON s.code_insee_commune = c.code_insee_commune
                        LEFT JOIN a_des ad ON pdc.id_pdc = ad.id_pdc
                        WHERE pdc.lat IS NOT NULL AND pdc.lon IS NOT NULL
                    ';
                }

                $whereClauses = [];
                $queryParams = [];

                if (!empty($filters['annee'])) {
                    $whereClauses[] = 'YEAR(s.date_mise_en_service) = :annee';
                    $queryParams[':annee'] = (int)$filters['annee'];
                }
                if (!empty($filters['code_dep'])) {
                    $whereClauses[] = 'c.code_dep = :code_dep';
                    $queryParams[':code_dep'] = $filters['code_dep'];
                }

                // Filtrage géographique par Bounding Box
                if ($filters['min_lat'] !== null && $filters['max_lat'] !== null && $filters['min_lng'] !== null && $filters['max_lng'] !== null) {
                    $whereClauses[] = 'pdc.lat BETWEEN :min_lat AND :max_lat';
                    // On prend la valeur absolue pour la longitude négative dans le BETWEEN si nécessaire, ou plus simplement :
                    $whereClauses[] = 'pdc.lon BETWEEN :min_lng AND :max_lng';
                    $queryParams[':min_lat'] = $filters['min_lat'];
                    $queryParams[':max_lat'] = $filters['max_lat'];
                    $queryParams[':min_lng'] = $filters['min_lng'];
                    $queryParams[':max_lng'] = $filters['max_lng'];
                }

                if (!empty($whereClauses)) {
                    $request .= ' AND ' . implode(' AND ', $whereClauses);
                }

                // Groupement SQL si zoom < 10
                if ($zoom > 0 && $zoom < 10) {
                    $request .= ' GROUP BY s.id_station_itinerance';
                }

                $statement = $this->db->prepare($request);
                $statement->execute($queryParams);
                $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

                return array_map(function($row) {
                    return [
                        'id' => (int)$row['id'],
                        'nom_station' => $row['nom_station'],
                        'adresse_station' => $row['adresse_station'],
                        'localite' => $row['localite'],
                        'dept' => (int)$row['dept'],
                        'annee' => $row['annee'] !== null ? (int)$row['annee'] : null,
                        'puissance' => $row['puissance'] !== null ? (float)$row['puissance'] : null,
                        'type_prise' => $row['type_prise'],
                        'lat' => (float)$row['lat'],
                        'lng' => -abs((float)$row['lng']),
                        'count_pdc' => (int)$row['count_pdc']
                    ];
                }, $rows);
            } catch (PDOException $exception) {
                error_log('getMapPoints error: ' . $exception->getMessage());
                return [];
            }
        }

        /**
         * Supprime un point de charge (PDC) et ses relations.
         * Supprime séquentiellement toutes les clés étrangères dépendantes (moyens de paiement, types de prise, association de station)
         * au sein d'une transaction SQL unifiée pour préserver l'intégrité référentielle.
         * 
         * @param int $id_pdc Identifiant unique de la borne à supprimer.
         * @return bool Vrai si la suppression a réussi, faux en cas d'erreur.
        */
        public function delete(int $id_pdc): bool {
            try {
                $this->db->beginTransaction();

                // 1. Suppression des associations de paiement
                $stmt = $this->db->prepare('DELETE FROM est_payer_avec WHERE id_pdc = :id_pdc');
                $stmt->execute([':id_pdc' => $id_pdc]);

                // 2. Suppression des associations de type de prise
                $stmt = $this->db->prepare('DELETE FROM a_des WHERE id_pdc = :id_pdc');
                $stmt->execute([':id_pdc' => $id_pdc]);

                // 3. Suppression des associations avec la station
                $stmt = $this->db->prepare('DELETE FROM possede_des WHERE id_pdc = :id_pdc');
                $stmt->execute([':id_pdc' => $id_pdc]);

                // 4. Suppression du point de charge
                $stmt = $this->db->prepare('DELETE FROM point_de_charge WHERE id_pdc = :id_pdc');
                $stmt->execute([':id_pdc' => $id_pdc]);

                $this->db->commit();
                return true;
            } catch (PDOException $exception) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                error_log('Delete error: ' . $exception->getMessage());
                return false;
            }
        }
    }
?>