<?php
session_start();
$_SESSION['user'] = 'testeur';

require_once __DIR__ . '/config_admin.php';
require_once ROOT_PATH . 'src/core/component_model.php';

header('Content-Type: text/plain; charset=utf-8');

$langs = ['fr', 'en'];
$model = new ComponentModel(JSON_ARTICLES_DIR, $langs, 'article');

echo "=== TEST ComponentModel ===\n\n";

// Test 1 : Création d'un composant vide
echo "1. Création composant vide :\n";
$empty = $model->createEmpty("Mon Premier Article");
echo "   ID généré: " . $empty['meta']['id'] . "\n";
echo "   Auteur: " . $empty['meta']['author'] . "\n";

echo "\n";

// Test 2 : Sauvegarde avec validation
echo "2. Sauvegarde avec blocs valides :\n";
$article = [
    'type' => 'article',
    'meta' => [
        'id' => 'test-component-model',
        'created' => date('Y-m-d'),
        'status' => 'draft'
    ],
    'content' => [
        [
            'type' => 'title',
            'level' => 2,
            'data' => ['fr' => 'Titre de test', 'en' => 'Test title']
        ],
        [
            'type' => 'text',
            'data' => ['fr' => 'Contenu français', 'en' => 'English content']
        ]
    ]
];

$result = $model->save($article);
echo "   Succès: " . ($result['success'] ? 'OUI' : 'NON') . "\n";
if ($result['success']) {
    echo "   Fichier: " . $result['filename'] . "\n";
}

echo "\n";

// Test 3 : Sauvegarde avec bloc invalide
echo "3. Sauvegarde avec bloc INVALIDE :\n";
$badArticle = [
    'type' => 'article',
    'meta' => ['id' => 'test-invalide'],
    'content' => [
        [
            'type' => 'link',
            'data' => ['fr' => 'Cliquer']
            // URL manquante, 'en' manquant
        ]
    ]
];

$result = $model->save($badArticle);
echo "   Succès: " . ($result['success'] ? 'OUI' : 'NON') . "\n";
echo "   Erreurs:\n";
foreach ($result['errors'] as $err) {
    echo "   - {$err}\n";
}

echo "\n";

// Test 4 : Liste des composants
echo "4. Liste des composants :\n";
$list = $model->listAll(true);
foreach ($list as $comp) {
    echo "   - {$comp['id']} ({$comp['status']}) - {$comp['blocksCount']} blocs\n";
}

echo "\n";

// Test 5 : Chargement
echo "5. Chargement du composant créé :\n";
try {
    $loaded = $model->load('test-component-model');
    echo "   ID: " . $loaded['meta']['id'] . "\n";
    echo "   Blocs: " . count($loaded['content']) . "\n";
} catch (Exception $e) {
    echo "   Erreur: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DES TESTS ===\n";