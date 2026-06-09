<?php 
    require_once __DIR__ . '/Database.php';
    require_once __DIR__ . '/models/Stats.php';
    require_once __DIR__ . '/models/Referentiel.php';
    require_once __DIR__ . '/models/PointDeCharge.php';

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
        try {
            $statsModel = new Stats($db);
            $data = [
                'total_pdc' => $statsModel->getNbrPDC(),
                'total_amenageurs' => $statsModel->getNbrAmenageurs(),
                'departments' => $statsModel->getNbrPDCParDepartements(),
                'pdc_par_annee' => $statsModel->getNbrPDCParAnnees(),
                'pdc_par_annee_departement' => $statsModel->getNbrPDCDepartementAnnees(),
                'pdc_par_type_prise' => $statsModel->getNbrPDCParTypeDePrise()
            ];
        } catch (Exception $e) {
            $data = false;
        }
    }

    if ($requestRessource === 'referentiel' && $requestMethod === 'GET') {
        try {
            $refModel = new Referentiel($db);
            $data = [
                'types_prise' => $refModel->getTypesPrise(),
                'amenageurs' => $refModel->getAmenageurs(),
                'departements' => $refModel->getDepartements(),
                'annees' => $refModel->getAnneeMiseEnService()
            ];
        } catch (Exception $e) {
            $data = false;
        }
    }

    if ($requestRessource === 'pdc' && $requestMethod === 'GET') {
        try {
            $pdcModel = new PointDeCharge($db);
            
            $subResource = array_shift($request);
            if ($subResource === 'detail') {
                $id_pdc = isset($_GET['id_pdc']) ? (int)$_GET['id_pdc'] : 0;
                
                $pdc = $id_pdc ? $pdcModel->getById($id_pdc) : null;
                if ($pdc) {
                    $data = $pdc;
                } else {
                    $data = false;
                }
            } else if ($subResource === 'map') {
                $filters = [
                    'annee' => isset($_GET['annee']) ? trim($_GET['annee']) : '',
                    'code_dep' => isset($_GET['code_dep']) ? trim($_GET['code_dep']) : '',
                    'zoom' => isset($_GET['zoom']) ? (int)$_GET['zoom'] : 0,
                    'min_lat' => isset($_GET['min_lat']) ? (float)$_GET['min_lat'] : null,
                    'max_lat' => isset($_GET['max_lat']) ? (float)$_GET['max_lat'] : null,
                    'min_lng' => isset($_GET['min_lng']) ? (float)$_GET['min_lng'] : null,
                    'max_lng' => isset($_GET['max_lng']) ? (float)$_GET['max_lng'] : null
                ];
                if (empty($filters['annee']) && empty($filters['code_dep'])) {
                    $data = [];
                } else {
                    $data = $pdcModel->getMapPoints($filters);
                }
            } else {
                $limit = 100;
                $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                $offset = ($page - 1) * $limit;
                
                // Extraction des filtres optionnels depuis les paramètres de l'URL
                $filters = [
                    'amenageur' => isset($_GET['amenageur']) ? trim($_GET['amenageur']) : '',
                    'type_prise' => isset($_GET['type_prise']) ? trim($_GET['type_prise']) : '',
                    'code_dep' => isset($_GET['code_dep']) ? trim($_GET['code_dep']) : ''
                ];
                
                $total = $pdcModel->searchCount($filters);
                $pdcs = $pdcModel->search($filters, $limit, $offset);
                
                $data = [
                    'total' => $total,
                    'pages' => $total > 0 ? (int)ceil($total / $limit) : 1,
                    'page' => $page,
                    'limit' => $limit,
                    'pdcs' => $pdcs
                ];
            }
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