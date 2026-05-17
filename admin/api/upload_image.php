<?php
require_once __DIR__ . '/../config_admin.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

require_once ROOT_PATH . 'admin/src/image_uploader.class.php';

header('Content-Type: application/json; charset=utf-8');

$dir = trim($_POST['dir'] ?? '');

if ($dir === '') {
    echo json_encode(['success' => false, 'error' => 'Répertoire manquant']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Fichier manquant ou erreur upload']);
    exit;
}

try {
    $uploader = new ImageUploader(DIR_IMG_CONTENT, $dir);
    $result   = $uploader->upload($_FILES['image']);
    echo json_encode(['success' => true, 'base' => $result['base'], 'ext' => $result['ext']]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
