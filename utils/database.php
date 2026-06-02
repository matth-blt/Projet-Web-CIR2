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

// function dbRequestStations($db) {
//     try {
//         $request = '
//             SELECT 
//                 s.id_station_itinerance,
//                 a_am.nom_acteur AS amenageur,
//                 a_op.nom_acteur AS operateur,
//                 ad.type_prise,
//                 c.nom_commune AS commune,
//                 pdc.tarification AS tarif_pdc
//             FROM station s
//             LEFT JOIN acteur a_am ON s.id_acteur = a_am.id_acteur
//             LEFT JOIN acteur a_op ON s.id_acteur_est_utiliser_par = a_op.id_acteur
//             LEFT JOIN commune c ON s.code_insee_commune = c.code_insee_commune
//             LEFT JOIN possede_des pd ON s.id_station_itinerance = pd.id_station_itinerance
//             LEFT JOIN point_de_charge pdc ON pd.id_pdc = pdc.id_pdc
//             LEFT JOIN a_des ad ON pd.id_pdc = ad.id_pdc LIMIT 5;';
//         $statement = $db->prepare($request);
//         $statement->execute();
//         $result = $statement->fetchAll(PDO::FETCH_ASSOC);  
//     } catch (PDOException $exception) {
//         error_log('Request error: ' . $exception->getMessage());
//         return false;
//     }
    
//     return $result;
// }

function dbRequestPDCS($db) {
    try {
        $request = '
            SELECT 
                s.nom_station,
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
            LEFT JOIN a_des ad ON pdc.id_pdc = ad.id_pdc LIMIT 5;   
        ';
        $statement = $db->prepare($request);
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);  
    } catch (PDOException $exception) {
        error_log('Request error: ' . $exception->getMessage());
        return false;
    }
    
    return $result;
}



?>