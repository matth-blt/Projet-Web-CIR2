<?php
require_once __DIR__ . '/Database.php';

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

// Endpoint : /stats (uniquement en GET)
if ($requestRessource === 'stats' && $requestMethod === 'GET') {
    require_once __DIR__ . '/models/Stats.php';
    try {
        $statsModel = new Stats($db);
        $data = [
            'total_elements' => $statsModel->getNbrElements(),
            'total_amenageurs' => $statsModel->getNbrAmenageurs(),
            'total_prises' => $statsModel->getNbrTypeDePrises(),
            'departments' => $statsModel->getNbrPDCParDepartements(),
            'pdc_par_annee' => $statsModel->getNbrPDCParAnnees()
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