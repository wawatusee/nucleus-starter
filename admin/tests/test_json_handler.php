<?php
/**
 * Test du JsonHandler
 * Accès : /admin/test_json_handler.php
 */

require_once __DIR__ . '/config_admin.php';
require_once ROOT_PATH . 'src/utils/json_handler.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== TEST JsonHandler ===\n\n";

$testFile = JSON_ARTICLES_DIR . 'test-nucleus.json';

// Test 1 : Lecture
echo "1. Test lecture...\n";
try {
    $data = JsonHandler::load($testFile);
    echo "   ✓ Fichier chargé\n";
    echo "   Type: " . $data['type'] . "\n";
    echo "   ID: " . $data['meta']['id'] . "\n";
    echo "   Blocs: " . count($data['content']) . "\n";
} catch (Exception $e) {
    echo "   ✗ Erreur: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2 : Modification + Sauvegarde
echo "2. Test écriture...\n";
try {
    $data['meta']['updated'] = date('Y-m-d');
    $data['content'][] = [
        'type' => 'text',
        'data' => [
            'fr' => 'Bloc ajouté par le test',
            'en' => 'Block added by test'
        ]
    ];
    
    JsonHandler::save($testFile, $data);
    echo "   ✓ Fichier sauvegardé\n";
    
    // Vérification
    $reloaded = JsonHandler::load($testFile);
    echo "   ✓ Vérification: " . count($reloaded['content']) . " blocs\n";
} catch (Exception $e) {
    echo "   ✗ Erreur: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3 : Listage
echo "3. Test listage...\n";
$files = JsonHandler::listFiles(JSON_ARTICLES_DIR);
echo "   Articles trouvés: " . count($files) . "\n";
foreach ($files as $f) {
    echo "   - {$f}\n";
}

echo "\n=== FIN DES TESTS ===\n";