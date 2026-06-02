<?php require_once('database.php');

$db = dbConnect();
if (!$db) {
    header('HTTP/1.1 503 Service Unavailable');
    exit;
}

$requestMethod = $_SERVER['REQUEST_METHOD'];
$request = substr($_SERVER['PATH_INFO'], 1);
$request = explode('/', $request);
$requestRessource = array_shift($request);

$id = array_shift($request);
if ($id == '')
    $id = NULL;
$data = false;

if ($requestRessource == 'pdcs') {
    $data = dbRequestPDCS($db);
}

if ($data !== false) {
    header('HTTP/1.1 200 OK');
    echo json_encode($data);
} else {
    header('HTTP/1.1 400 Bad Request');
}

exit;
?>