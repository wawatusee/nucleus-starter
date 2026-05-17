<?php
require_once __DIR__ . '/../config_admin.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$jsonInput = file_get_contents('php://input');
$data      = json_decode($jsonInput, true);

$dir      = basename($data['dir']      ?? '');
$filename = basename($data['filename'] ?? '');

if ($dir === '' || $filename === '') {
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
    exit;
}

$base     = DIR_IMG_CONTENT . $dir . DIRECTORY_SEPARATOR;
$original = $base . $filename;
$thumb    = $base . 'thumbs' . DIRECTORY_SEPARATOR . $filename;

if (!file_exists($original)) {
    echo json_encode(['success' => false, 'error' => 'Fichier introuvable']);
    exit;
}

if (file_exists($original)) unlink($original);
if (file_exists($thumb))    unlink($thumb);

echo json_encode(['success' => true]);
