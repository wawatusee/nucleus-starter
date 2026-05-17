<?php
require_once __DIR__ . '/config_admin.php';
require_once ROOT_PATH . 'src/core/block_registry.php';

header('Content-Type: text/plain; charset=utf-8');

$langs = ['fr', 'en'];

echo "=== TEST BlockRegistry ===\n\n";

// Test 1 : Types disponibles
echo "1. Types disponibles :\n";
foreach (BlockRegistry::getOptions() as $key => $label) {
    echo "   - {$key} : {$label}\n";
}

echo "\n";

// Test 2 : Validation d'un bloc valide
echo "2. Bloc VALIDE :\n";
$validBlock = [
    'type' => 'title',
    'level' => 2,
    'data' => [
        'fr' => 'Mon titre',
        'en' => 'My title'
    ]
];
$result = BlockRegistry::validate($validBlock, $langs);
echo "   Valid: " . ($result['valid'] ? 'OUI' : 'NON') . "\n";

echo "\n";

// Test 3 : Validation d'un bloc invalide
echo "3. Bloc INVALIDE (level=10, url manquante) :\n";
$invalidBlock = [
    'type' => 'link',
    'data' => [
        'fr' => 'Cliquez ici'
        // 'en' manquant
    ]
];
$result = BlockRegistry::validate($invalidBlock, $langs);
echo "   Valid: " . ($result['valid'] ? 'OUI' : 'NON') . "\n";
echo "   Erreurs:\n";
foreach ($result['errors'] as $err) {
    echo "   - {$err}\n";
}

echo "\n";

// Test 4 : Normalisation
echo "4. Normalisation d'un bloc incomplet :\n";
$incompleteBlock = [
    'type' => 'title',
    'data' => [
        'fr' => 'Titre FR'
    ]
];
$normalized = BlockRegistry::normalize($incompleteBlock, $langs);
echo "   Level après normalisation: " . $normalized['level'] . "\n";
echo "   EN après normalisation: '" . $normalized['data']['en'] . "'\n";

echo "\n=== FIN DES TESTS ===\n";