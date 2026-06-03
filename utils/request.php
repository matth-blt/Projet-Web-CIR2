<?php require_once('database.php');

$db = dbConnect();
if (!$db) {
    header('HTTP/1.1 503 Service Unavailable');
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
        $data = dbRequestPDCS($db, true);
    } else {
        $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        $page   = isset($_GET['page'])  ? max(1, (int)$_GET['page']) : 1;
        $offset = ($page - 1) * $limit;

        $total = dbCountPDCS($db);
        header('X-Total-Count: ' . $total);
        header('Access-Control-Expose-Headers: X-Total-Count');

        $data = dbRequestPDCS($db, false, $limit, $offset);
    }
}

if ($requestMethod == 'GET') {
    $type_prise = isset($_GET['type_prise']) ? $_GET['type_prise'] : null;

    if ($id_pdc !== null) {
        $data = dbRequestPDC($db, $id_pdc, $type_prise);
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