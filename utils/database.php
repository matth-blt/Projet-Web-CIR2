<?php require_once('constants.php');

function dbConnect() {
    try {
        $db = new PDO(
            'mysql:host=' . DB_SERVER . 
            ';dbname=' . DB_NAME . 
            ';charset=utf8;' . 
            'port='.DB_PORT, DB_USER, DB_PASSWORD
        );
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
    } catch (PDOException $exception) {
        error_log('Connection error: '.$exception->getMessage());
        return false;
    }
    
    return $db;
}

function dbCountPDCS($db) {
    try {
        $request = '
            SELECT COUNT(*) AS total
            FROM point_de_charge pdc
            LEFT JOIN a_des ad ON pdc.id_pdc = ad.id_pdc
        ';
        $statement = $db->prepare($request);
        $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return (int) $row['total'];
    } catch (PDOException $exception) {
        error_log('Count error: ' . $exception->getMessage());
        return 0;
    }
}

function dbTypesDePrises($db) {
    try {
        $statement = $db->prepare('SELECT type_prise FROM prise ORDER BY type_prise;');
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $exception) {
        error_log('Request error: ' . $exception->getMessage());
        return [];
    }
}

function dbTypesDePaiements($db) {
    try {
        $statement = $db->prepare('SELECT type_paiement FROM paiement ORDER BY type_paiement;');
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $exception) {
        error_log('Request error: ' . $exception->getMessage());
        return [];
    }
}

function dbRequestPDCS($db, $accueil = false, $limit = null, $offset = 0) {
    try {
        $request = '
            SELECT 
                s.nom_station,
                pdc.id_pdc,
                a_am.nom_acteur AS amenageur,
                a_op.nom_acteur AS operateur,
                ad.type_prise,
                c.nom_commune AS commune,
                pdc.tarification
            FROM point_de_charge pdc
            LEFT JOIN possede_des pd ON pdc.id_pdc = pd.id_pdc
            LEFT JOIN station s ON pd.id_station_itinerance = s.id_station_itinerance
            LEFT JOIN acteur a_am ON s.id_acteur = a_am.id_acteur
            LEFT JOIN acteur a_op ON s.id_acteur_est_utiliser_par = a_op.id_acteur
            LEFT JOIN commune c ON s.code_insee_commune = c.code_insee_commune
            LEFT JOIN a_des ad ON pdc.id_pdc = ad.id_pdc
        ';
        
        if ($accueil == true) {
            $request = $request . ' ORDER BY RAND() LIMIT 5';
        } else if ($limit !== null) {
            $request = $request . ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
        }

        $request = $request . ';';

        $statement = $db->prepare($request);
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);  
    } catch (PDOException $exception) {
        error_log('Request error: ' . $exception->getMessage());
        return false;
    }
    
    return $result;
}

function dbRequestPDC($db, $id_pdc, $type_prise) {
    try {
        $request = '
                SELECT 
                    s.nom_station,
                    pdc.id_pdc,
                    a_am.nom_acteur AS amenageur,
                    a_am.siren_acteur AS siren_amenageur,
                    a_op.nom_acteur AS operateur,
                    a_op.contact_acteur AS contact_operateur,
                    ad.type_prise,
                    c.nom_commune AS commune,
                    dep.nom_departement AS departement,
                    pdc.puissance,
                    pdc.cable_t2_attache,
                    pdc.lat AS latitude,
                    pdc.lon AS longitude,
                    pdc.tarification,
                    pdc.pdc_condition AS condition_acces,
                GROUP_CONCAT(DISTINCT epa.type_paiement SEPARATOR \', \') AS types_paiement
                FROM point_de_charge pdc
                LEFT JOIN possede_des pd ON pdc.id_pdc = pd.id_pdc
                LEFT JOIN station s ON pd.id_station_itinerance = s.id_station_itinerance
                LEFT JOIN acteur a_am ON s.id_acteur = a_am.id_acteur
                LEFT JOIN acteur a_op ON s.id_acteur_est_utiliser_par = a_op.id_acteur
                LEFT JOIN commune c ON s.code_insee_commune = c.code_insee_commune
                LEFT JOIN departement dep ON c.code_dep = dep.code_dep
                LEFT JOIN a_des ad ON pdc.id_pdc = ad.id_pdc
                LEFT JOIN est_payer_avec epa ON pdc.id_pdc = epa.id_pdc
                WHERE pdc.id_pdc = :id_pdc AND ad.type_prise = :type_prise
                GROUP BY 
                    pdc.id_pdc, 
                    ad.type_prise, 
                    s.nom_station, 
                    a_am.nom_acteur, 
                    a_am.siren_acteur, 
                    a_op.nom_acteur, 
                    a_op.contact_acteur, 
                    c.nom_commune, 
                    dep.nom_departement,
                    pdc.puissance,
                    pdc.cable_t2_attache,
                    pdc.lat,
                    pdc.lon,
                    pdc.tarification,
                    pdc.pdc_condition; 
            ';

        $statement = $db->prepare($request);
        $statement->execute([
            ':id_pdc' => $id_pdc,
            ':type_prise' => $type_prise
        ]);

        $result = $statement->fetchAll(PDO::FETCH_ASSOC);  
    } catch (PDOException $exception) {
        error_log('Request error: ' . $exception->getMessage());
        return false;
    }
    
    return $result;
}

function dbUpdatePDC($db, $data) {
    try {
        $stmt = $db->prepare('
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

function dbAddPDC($db, $data) {
    try {
        $db->beginTransaction();

        // 1. Département (INSERT IGNORE : peut déjà exister)
        $db->prepare('INSERT IGNORE INTO departement (code_dep, nom_departement) VALUES (:code_dep, :nom_dep)')
           ->execute([':code_dep' => $data['code_dep'], ':nom_dep' => $data['nom_departement']]);

        // 2. Commune (INSERT IGNORE : peut déjà exister)
        $db->prepare('INSERT IGNORE INTO commune (code_insee_commune, nom_commune, code_dep) VALUES (:code_insee, :nom_commune, :code_dep)')
           ->execute([':code_insee' => $data['code_insee'], ':nom_commune' => $data['nom_commune'], ':code_dep' => $data['code_dep']]);

        // 3. Acteur aménageur
        $stmtAm = $db->prepare('INSERT INTO acteur (siren_acteur, nom_acteur, contact_acteur, role_acteur) VALUES (:siren, :nom, :contact, :role)');
        $stmtAm->execute([':siren' => $data['siren_amenageur'], ':nom' => $data['nom_amenageur'], ':contact' => $data['contact_amenageur'] ?? null, ':role' => 'amenageur']);
        $id_acteur_am = $db->lastInsertId();

        // 4. Acteur opérateur
        $stmtOp = $db->prepare('INSERT INTO acteur (siren_acteur, nom_acteur, contact_acteur, telephone_acteur, role_acteur) VALUES (:siren, :nom, :contact, :telephone, :role)');
        $stmtOp->execute([':siren' => 0, ':nom' => $data['nom_operateur'], ':contact' => $data['contact_operateur'] ?? null, ':telephone' => $data['telephone_operateur'] ?? null, ':role' => 'operateur']);
        $id_acteur_op = $db->lastInsertId();

        // 5. ID station : généré si non fourni
        $id_station = !empty($data['id_station_itinerance'])
            ? $data['id_station_itinerance']
            : 'STA_' . time() . '_' . rand(100, 999);

        // 6. Station
        $db->prepare('
            INSERT INTO station (id_station_itinerance, nom_station, adresse_station, nbr_pdc, date_mise_en_service, code_insee_commune, id_acteur, id_acteur_est_utiliser_par)
            VALUES (:id_sta, :nom, :adresse, 1, :date_service, :code_insee, :id_am, :id_op)
        ')->execute([
            ':id_sta'       => $id_station,
            ':nom'          => $data['nom_station'],
            ':adresse'      => $data['adresse_station'],
            ':date_service' => $data['date_service'] ?: null,
            ':code_insee'   => $data['code_insee'],
            ':id_am'        => $id_acteur_am,
            ':id_op'        => $id_acteur_op,
        ]);

        // 7. id_pdc : MAX + 1
        $row    = $db->query('SELECT COALESCE(MAX(id_pdc), 0) + 1 AS next_id FROM point_de_charge')->fetch(PDO::FETCH_ASSOC);
        $id_pdc = (int) $row['next_id'];

        // 8. Point de charge
        $db->prepare('
            INSERT INTO point_de_charge (id_pdc, lon, lat, puissance, cable_t2_attache, gratuit, tarification)
            VALUES (:id_pdc, :lon, :lat, :puissance, :cable, :gratuit, :tarification)
        ')->execute([
            ':id_pdc'      => $id_pdc,
            ':lon'         => $data['longitude'],
            ':lat'         => $data['latitude'],
            ':puissance'   => $data['puissance'],
            ':cable'       => (int)($data['cable_t2_attache'] ?? 0),
            ':gratuit'     => (int)($data['gratuit'] ?? 0),
            ':tarification'=> $data['tarification'] ?: null,
        ]);

        // 9. Relation pdc ↔ type de prise
        $db->prepare('INSERT INTO a_des (id_pdc, type_prise) VALUES (:id_pdc, :type_prise)')
           ->execute([':id_pdc' => $id_pdc, ':type_prise' => $data['type_prise']]);

        // 10. Relation station ↔ pdc
        $db->prepare('INSERT INTO possede_des (id_pdc, id_station_itinerance) VALUES (:id_pdc, :id_sta)')
           ->execute([':id_pdc' => $id_pdc, ':id_sta' => $id_station]);

        // 11. Types de paiement (0 ou plusieurs)
        if (!empty($data['types_paiement'])) {
            $stmtPay = $db->prepare('INSERT IGNORE INTO est_payer_avec (type_paiement, id_pdc) VALUES (:type, :id_pdc)');
            foreach ($data['types_paiement'] as $type) {
                $stmtPay->execute([':type' => $type, ':id_pdc' => $id_pdc]);
            }
        }

        $db->commit();
        return $id_pdc;

    } catch (PDOException $exception) {
        $db->rollBack();
        error_log('Insert error: ' . $exception->getMessage());
        return false;
    }
}

?>