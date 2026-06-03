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
    $data = dbRequestPDCS($db);
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