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

$pageId = basename($data['id'] ?? '');

if ($pageId === '') {
    echo json_encode(['success' => false, 'error' => 'Identifiant manquant']);
    exit;
}

// Vérifier que la page JSON existe
$jsonPath = JSON_PAGES_DIR . $pageId . '.json';
if (!file_exists($jsonPath)) {
    echo json_encode(['success' => false, 'error' => "json/pages/{$pageId}.json introuvable"]);
    exit;
}

// Refuser si le fichier PHP existe déjà
$phpPath = ROOT_PATH . 'inc/pages/' . $pageId . '.php';
if (file_exists($phpPath)) {
    echo json_encode(['success' => false, 'error' => "inc/pages/{$pageId}.php existe déjà"]);
    exit;
}

// Contenu standard
$content = <<<PHP
<?php
/**
 * Page : {$pageId}
 * Rendu piloté par json/pages/{$pageId}.json
 */
require_once ROOT_PATH . 'src/core/page_renderer.php';

\$renderer = new PageRenderer(APP_LANG);
\$renderer->render('{$pageId}');
PHP;

if (file_put_contents($phpPath, $content) === false) {
    echo json_encode(['success' => false, 'error' => 'Impossible d\'écrire le fichier']);
    exit;
}

echo json_encode(['success' => true, 'file' => "inc/pages/{$pageId}.php"]);
