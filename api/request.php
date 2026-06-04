<?php require_once __DIR__ . '/Database.php';

// Connexion à la base de données
try {
    $db = Database::getConnection();
} catch (Exception $e) {
    header('HTTP/1.1 503 Service Unavailable');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$requestMethod = $_SERVER['REQUEST_METHOD'];
$pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
$request = explode('/', trim($pathInfo, '/'));
$requestRessource = array_shift($request);

$data = false;

if ($requestRessource === 'stats' && $requestMethod === 'GET') {
    require_once __DIR__ . '/models/Stats.php';
    try {
        $statsModel = new Stats($db);
        $data = [
            'total_elements' => $statsModel->getNbrElements(),
            'total_amenageurs' => $statsModel->getNbrAmenageurs(),
            'total_prises' => $statsModel->getNbrTypeDePrises(),
            'departments' => $statsModel->getNbrPDCParDepartements(),
            'pdc_par_annee' => $statsModel->getNbrPDCParAnnees(),
            'pdc_par_annee_departement' => $statsModel->getNbrPDCDepartementAnnees()
        ];
    } catch (Exception $e) {
        $data = false;
    }
}

if ($requestRessource === 'referentiel' && $requestMethod === 'GET') {
    require_once __DIR__ . '/models/Referentiel.php';
    try {
        $refModel = new Referentiel($db);
        $data = [
            'types_prise' => $refModel->getTypesPrise(),
            'amenageurs' => $refModel->getAmenageurs(),
            'departements' => $refModel->getDepartements()
        ];
    } catch (Exception $e) {
        $data = false;
    }
}

if ($requestRessource === 'pdc' && $requestMethod === 'GET') {
    require_once __DIR__ . '/models/PointDeCharge.php';
    try {
        $pdcModel = new PointDeCharge($db);
        $limit = 100;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $offset = ($page - 1) * $limit;
        
        $total = $pdcModel->count();
        $pdcs = $pdcModel->getAll(false, $limit, $offset);
        
        $data = [
            'total' => $total,
            'pages' => $total > 0 ? (int)ceil($total / $limit) : 1,
            'page' => $page,
            'limit' => $limit,
            'pdcs' => $pdcs
        ];
    } catch (Exception $e) {
        $data = false;
    }
}

if ($data !== false) {
    header('HTTP/1.1 200 OK');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} else {
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Resource not found or bad request']);
}
exit;
?>