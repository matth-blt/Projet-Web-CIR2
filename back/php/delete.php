<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../api/Database.php';
require_once __DIR__ . '/../../api/models/PointDeCharge.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Erreur : Méthode non autorisée. La suppression doit se faire en POST.');
}

$csrf = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    die('Erreur CSRF : Action non autorisée.');
}

$id_pdc = $_POST['id_pdc'] ?? '';
$redirect = $_POST['redirect'] ?? 'liste';

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
