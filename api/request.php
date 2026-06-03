<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/models/PointDeCharge.php';

try {
    $db = Database::getConnection();
    $pdcModel = new PointDeCharge($db);
} catch (Exception $e) {
    header('HTTP/1.1 503 Service Unavailable');
    echo json_encode(['error' => 'Database unavailable']);
    exit;
}

$requestMethod = $_SERVER['REQUEST_METHOD'];

$pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
$request = explode('/', trim($pathInfo, '/'));
$requestRessource = array_shift($request);

$id_pdc = array_shift($request);
if ($id_pdc == '') {
    $id_pdc = isset($_GET['id_pdc']) ? $_GET['id_pdc'] : null;
}

$data = false;

if ($requestRessource == 'pdcs' && $id_pdc === null) {
    $accueil = isset($_GET['accueil']) && $_GET['accueil'] === 'true';

    if ($accueil) {
        $data = $pdcModel->getAll(accueil: true);
    } else {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        $page = isset($_GET['page'])  ? max(1, (int)$_GET['page']) : 1;
        $offset = ($page - 1) * $limit;

        $total = $pdcModel->count();
        header('X-Total-Count: ' . $total);
        header('Access-Control-Expose-Headers: X-Total-Count');

        $data = $pdcModel->getAll(false, $limit, $offset);
    }
}

if ($requestMethod == 'GET') {
    $type_prise = isset($_GET['type_prise']) ? $_GET['type_prise'] : null;

    if ($id_pdc !== null) {
        $data = $pdcModel->getById((int)$id_pdc, $type_prise);
    }
}

if ($requestMethod == 'POST') {
    if ($id_pdc !== null) {
        $body = json_decode(file_get_contents('php://input'), true);

        if (!$body) {
            header('HTTP/1.1 400 Bad Request');
            exit;
        }

        $data = $pdcModel->update([
            'id_pdc' => $id_pdc,
            'puissance' => $body['puissance'] ?? null,
            'cable_t2_attache' => $body['cable_t2_attache'] ?? 0,
            'latitude' => $body['latitude'] ?? null,
            'longitude' => $body['longitude'] ?? null,
            'tarification' => $body['tarification'] ?? null,
        ]);
    }
}

if ($data !== false) {
    header('HTTP/1.1 200 OK');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} else {
    header('HTTP/1.1 400 Bad Request');
}

exit;
?>