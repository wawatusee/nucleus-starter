<?php
require_once __DIR__ . '/../config_admin.php';
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}
require_once ROOT_PATH . 'src/core/page_model.php';

header('Content-Type: application/json; charset=utf-8');

$withMeta = isset($_GET['meta']) && $_GET['meta'] === '1';

$model = new PageModel(JSON_PAGES_DIR);
$list = $model->listAll($withMeta);

echo json_encode($list);
