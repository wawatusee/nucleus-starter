<?php
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

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'JSON invalide']);
    exit;
}

$langs = array_column(ConfigModel::getLangs(), 'code');
$model = new ComponentModel(JSON_ARTICLES_DIR, $langs, 'article');

$result = $model->save($data);

echo json_encode($result);
