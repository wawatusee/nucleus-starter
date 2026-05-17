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

$file = $_GET['file'] ?? null;

if (!$file) {
    echo json_encode(['success' => false, 'error' => 'Paramètre file manquant']);
    exit;
}

$langs = array_column(ConfigModel::getLangs(), 'code');
$model = new ComponentModel(JSON_ARTICLES_DIR, $langs, 'article');

try {
    $data = $model->load($file);
    echo json_encode($data);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
