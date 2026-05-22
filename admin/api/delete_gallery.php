<?php
require_once __DIR__ . '/../config_admin.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

require_once ROOT_PATH . 'src/utils/json_handler.php';

header('Content-Type: application/json; charset=utf-8');

$jsonInput = file_get_contents('php://input');
$data      = json_decode($jsonInput, true);

$folder = basename($data['folder'] ?? '');

if ($folder === '') {
    echo json_encode(['success' => false, 'error' => 'Paramètre folder manquant']);
    exit;
}

$path = JSON_GALLERIES_DIR . $folder . '.json';

if (!file_exists($path)) {
    echo json_encode(['success' => false, 'error' => "Galerie '{$folder}' introuvable"]);
    exit;
}

try {
    JsonHandler::delete($path);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
