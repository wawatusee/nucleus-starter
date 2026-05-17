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

$dir     = basename($data['dir']      ?? '');
$oldName = basename($data['old_name'] ?? '');
$newName = $data['new_name'] ?? '';

if ($dir === '' || $oldName === '' || $newName === '') {
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
    exit;
}

// Slugify du nouveau nom
$newSlug = strtolower(trim($newName));
$newSlug = preg_replace('/[àáâãäå]/u', 'a', $newSlug);
$newSlug = preg_replace('/[èéêë]/u',   'e', $newSlug);
$newSlug = preg_replace('/[ìíîï]/u',   'i', $newSlug);
$newSlug = preg_replace('/[òóôõö]/u',  'o', $newSlug);
$newSlug = preg_replace('/[ùúûü]/u',   'u', $newSlug);
$newSlug = preg_replace('/[ç]/u',      'c', $newSlug);
$newSlug = preg_replace('/[^a-z0-9]+/', '-', $newSlug);
$newSlug = trim($newSlug, '-') . '.jpg';

$base        = DIR_IMG_CONTENT . $dir . DIRECTORY_SEPARATOR;
$oldOriginal = $base . $oldName;
$oldThumb    = $base . 'thumbs' . DIRECTORY_SEPARATOR . $oldName;
$newOriginal = $base . $newSlug;
$newThumb    = $base . 'thumbs' . DIRECTORY_SEPARATOR . $newSlug;

if (!file_exists($oldOriginal)) {
    echo json_encode(['success' => false, 'error' => 'Fichier introuvable']);
    exit;
}

if (file_exists($newOriginal)) {
    echo json_encode(['success' => false, 'error' => "'{$newSlug}' existe déjà"]);
    exit;
}

rename($oldOriginal, $newOriginal);
if (file_exists($oldThumb)) rename($oldThumb, $newThumb);

echo json_encode(['success' => true, 'filename' => $newSlug]);
