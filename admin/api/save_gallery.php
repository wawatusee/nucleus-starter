<?php
require_once __DIR__ . '/../config_admin.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

require_once ROOT_PATH . 'src/utils/json_handler.php';

header('Content-Type: application/json; charset=utf-8');

$jsonInput = file_get_contents('php://input');
$data      = json_decode($jsonInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'JSON invalide']);
    exit;
}

// Validation structure
$errors = [];
$folder = basename($data['folder'] ?? '');

if ($folder === '') {
    $errors[] = 'folder manquant';
}

if (!isset($data['title']) || !is_array($data['title'])) {
    $errors[] = 'title manquant ou invalide';
}

if (!isset($data['images']) || !is_array($data['images'])) {
    $errors[] = 'images manquant ou invalide';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Validation des images
$langs = array_column(ConfigModel::getLangs(), 'code');

foreach ($data['images'] as $i => $image) {
    $num = $i + 1;
    if (empty($image['src'])) {
        $errors[] = "Image #{$num} : src manquant";
    }
    if (!isset($image['alt']) || !is_array($image['alt'])) {
        $errors[] = "Image #{$num} : alt manquant ou invalide";
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Normalisation — langues pilotées par config.json
// title
$normalizedTitle = [];
foreach ($langs as $lang) {
    $normalizedTitle[$lang] = $data['title'][$lang] ?? '';
}
$data['title'] = $normalizedTitle;

// alt et caption de chaque image
foreach ($data['images'] as &$image) {
    $normalizedAlt     = [];
    $normalizedCaption = [];
    foreach ($langs as $lang) {
        $normalizedAlt[$lang]     = $image['alt'][$lang]     ?? '';
        $normalizedCaption[$lang] = $image['caption'][$lang] ?? '';
    }
    $image['alt']     = $normalizedAlt;
    $image['caption'] = $normalizedCaption;
}
unset($image);

// Structure finale
$gallery = [
    'type'   => 'gallery_ref',
    'folder' => $folder,
    'title'  => $data['title'],
    'images' => $data['images']
];

// Sauvegarde
$path = JSON_GALLERIES_DIR . $folder . '.json';

try {
    JsonHandler::save($path, $gallery);
    echo json_encode(['success' => true, 'filename' => $folder . '.json']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
