<?php
// test_menus_model.php — à la racine de /tests/ ou /admin/
require_once '../config/config.php';

$ok = 0;
$fail = 0;

function assert_test(string $label, bool $condition): void {
    global $ok, $fail;
    if ($condition) {
        echo "✓ $label\n";
        $ok++;
    } else {
        echo "✗ $label\n";
        $fail++;
    }
}

// --- Tests ---

// Fichier valide
$menus = new MenusModel(DIR_JSON . 'menus.json');
assert_test('getMenu retourne un array pour Main_menu',  is_array($menus->getMenu('Main_menu')));
assert_test('getMenu retourne null pour un type inconnu', $menus->getMenu('Inexistant') === null);
assert_test('Premier item a une propriété page',          isset($menus->getMenu('Main_menu')[0]->page));

// Fichier invalide
try {
    $bad = new MenusModel(DIR_JSON . 'inexistant.json');
    assert_test('Exception si fichier absent', false);
} catch (RuntimeException $e) {
    assert_test('Exception si fichier absent', true);
}

// --- Résumé ---
echo "\n$ok passed, $fail failed\n";