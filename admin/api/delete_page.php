<?php

require_once __DIR__ . '/../config_admin.php';
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}


require_once ROOT_PATH . 'src/core/page_model.php';

header('Content-Type: application/json; charset=utf-8');

$jsonInput = file_get_contents('php://input');
$data = json_decode($jsonInput, true);

$filename = $data['filename'] ?? null;

if (!$filename) {
    echo json_encode(['success' => false, 'error' => 'Nom de fichier manquant']);
    exit;
}

$model = new PageModel(JSON_PAGES_DIR);
$result = $model->delete($filename);

echo json_encode($result);
