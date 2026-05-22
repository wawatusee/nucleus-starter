<?php
require_once __DIR__ . '/../config_admin.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$galleries = [];

if (is_dir(JSON_GALLERIES_DIR)) {
    $files = array_diff(scandir(JSON_GALLERIES_DIR), ['..', '.']);
    foreach ($files as $file) {
        if (str_ends_with($file, '.json')) {
            $galleries[] = str_replace('.json', '', $file);
        }
    }
}

echo json_encode(['success' => true, 'galleries' => $galleries]);
