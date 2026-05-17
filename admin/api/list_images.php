<?php
require_once __DIR__ . '/../config_admin.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$dir = trim($_GET['dir'] ?? '');

// Sans dir — retourne la liste des répertoires
if ($dir === '') {
    $dirs = [];
    foreach (glob(DIR_IMG_CONTENT . '*', GLOB_ONLYDIR) as $d) {
        $name = basename($d);
        if ($name !== 'thumbs') {
            $dirs[] = $name;
        }
    }
    echo json_encode(['success' => true, 'dirs' => $dirs]);
    exit;
}

// Avec dir — retourne les images du répertoire
$fullDir = DIR_IMG_CONTENT . basename($dir);
if (!is_dir($fullDir)) {
    echo json_encode(['success' => false, 'error' => 'Répertoire introuvable']);
    exit;
}

$thumbsDir = $fullDir . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR;
$images    = [];

if (is_dir($thumbsDir)) {
    $files = glob($thumbsDir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE);
    foreach ($files as $file) {
        $images[] = basename($file);
    }
}

echo json_encode(['success' => true, 'dir' => $dir, 'images' => $images]);