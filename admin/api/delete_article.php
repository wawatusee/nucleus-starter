<?php
//session_start();
require_once __DIR__ . '/../config_admin.php';
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}


//require_once ADMIN_PATH . 'src/model/config_model.php';
require_once ROOT_PATH . 'src/core/component_model.php';

header('Content-Type: application/json; charset=utf-8');

$jsonInput = file_get_contents('php://input');
$data = json_decode($jsonInput, true);

$filename = $data['filename'] ?? null;

if (!$filename) {
    echo json_encode(['success' => false, 'error' => 'Nom de fichier manquant']);
    exit;
}

$langs = array_column(ConfigModel::getLangs(), 'code');
$model = new ComponentModel(JSON_ARTICLES_DIR, $langs, 'article');

$result = $model->delete($filename);

echo json_encode($result);
