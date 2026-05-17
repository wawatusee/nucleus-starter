<?php
// Inclusion des ressources
require_once 'config_admin.php';
require_once './src/gallery_manager.class.php';

header('Content-Type: text/plain');

echo "=== DÉBUT DU TEST D'AUDIT ===\n";

try {
    // 1. Initialisation
    $baseDir = realpath(ROOT_PATH . 'public/img/content/galleries/');
    if (!$baseDir)
        throw new Exception("Dossier des galeries introuvable.");

    $mgr = new GalleryManager($baseDir);
    echo "✅ Manager initialisé.\n";

    // 2. Test de création
    $testName = "GALERIE_TEST_AUDIT";
    echo "--- Tentative de création de : $testName ---\n";

    // Si elle existe déjà, on la supprime pour repartir à zéro
    if (is_dir($baseDir . DIRECTORY_SEPARATOR . $testName)) {
        $mgr->deleteGallery($testName);
        echo "🗑️ Ancienne galerie de test supprimée.\n";
    }

    $mgr->createGallery($testName);
    echo "✅ Galerie créée physiquement.\n";

    // 3. Test de l'indexation JSON (Le Pont)
    if (file_exists($baseDir . DIRECTORY_SEPARATOR . 'galleries_index.json')) {
        $json = file_get_contents($baseDir . DIRECTORY_SEPARATOR . 'galleries_index.json');
        $data = json_decode($json, true);

        // Vérification de la présence de notre galerie dans le JSON
        $found = false;
        foreach ($data as $gal) {
            if ($gal['id'] === $testName) {
                $found = true;
                break;
            }
        }

        if ($found) {
            echo "✅ SUCCÈS : La galerie est présente dans l'index JSON.\n";
        } else {
            echo "❌ ERREUR : La galerie est absente de l'index.\n";
        }
    } else {
        echo "❌ ERREUR : Le fichier galleries_index.json n'a pas été généré.\n";
    }

} catch (Exception $e) {
    echo "💥 ERREUR FATALE : " . $e->getMessage() . "\n";
}

echo "=== FIN DU TEST ===";