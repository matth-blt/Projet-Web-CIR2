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

function dbRequestAmenageurs($db) {
    try {
      $request = 'SELECT siren_amenageur, nom_amenageur, contact_amenageur FROM amenageur';
      $statement = $db->prepare($request);
      $statement->execute();
      $result = $statement->fetchAll(PDO::FETCH_ASSOC);  
    } catch (PDOException $exception) {
      error_log('Request error: '.$exception->getMessage());
      return false;
    }
    
    return $result;
}


?>