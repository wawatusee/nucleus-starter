<?php
require_once __DIR__ . '/../config_admin.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

require_once ROOT_PATH . 'src/utils/json_handler.php';
require_once ROOT_PATH . 'src/core/page_model.php';

header('Content-Type: application/json; charset=utf-8');

$jsonInput = file_get_contents('php://input');
$data      = json_decode($jsonInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'JSON invalide']);
    exit;
}

// Validation structure minimale
if (!isset($data['Main_menu']) || !is_array($data['Main_menu'])) {
    echo json_encode(['success' => false, 'error' => 'Main_menu manquant ou invalide']);
    exit;
}

if (!isset($data['RS_menu']) || !is_array($data['RS_menu'])) {
    echo json_encode(['success' => false, 'error' => 'RS_menu manquant ou invalide']);
    exit;
}

// Validation des entrées
$langs  = array_column(ConfigModel::getLangs(), 'code');
$errors = [];

foreach ($data['Main_menu'] as $i => $entry) {
    $num = $i + 1;
    if (empty($entry['page'])) {
        $errors[] = "Entrée #{$num} : identifiant de page manquant";
    }
    if (!isset($entry['titre']) || !is_array($entry['titre'])) {
        $errors[] = "Entrée #{$num} : titre manquant";
    }
}

foreach ($data['RS_menu'] as $i => $entry) {
    $num = $i + 1;
    if (empty($entry['page'])) {
        $errors[] = "RS #{$num} : URL manquante";
    }
    if (empty($entry['titre'])) {
        $errors[] = "RS #{$num} : titre manquant";
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Normalisation — on ne garde que les langues de config.json
foreach ($data['Main_menu'] as &$entry) {
    $titre      = $entry['titre'] ?? [];
    $normalized = [];
    foreach ($langs as $lang) {
        $normalized[$lang] = $titre[$lang] ?? '';
    }
    $entry['titre'] = $normalized;
}
unset($entry);

// Création des pages manquantes
$pageModel = new PageModel(JSON_PAGES_DIR);
$created   = [];

foreach ($data['Main_menu'] as $entry) {
    $pageId = $entry['page'];
    if (!$pageModel->exists($pageId)) {
        $newPage = $pageModel->createEmpty($pageId);
        $result  = $pageModel->save($newPage);
        if ($result['success']) {
            $created[] = $pageId;
        }
    }
}

// Sauvegarde menus.json
$path = DIR_JSON . 'menus.json';

try {
    JsonHandler::save($path, $data);
    echo json_encode([
        'success' => true,
        'created' => $created
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
