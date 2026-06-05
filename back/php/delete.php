<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../api/Database.php';
require_once __DIR__ . '/../../api/models/PointDeCharge.php';

$id_pdc = $_GET['id_pdc'] ?? '';
$redirect = $_GET['redirect'] ?? 'liste';

if ($id_pdc) {
    $db = Database::getConnection();
    $pdcModel = new PointDeCharge($db);
    $pdcModel->delete((int)$id_pdc);
}

if ($redirect === 'accueil') {
    header('Location: ../index.php');
} else {
    header('Location: liste.php');
}
exit;
