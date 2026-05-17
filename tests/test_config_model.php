<?php
require_once '../config/config.php';

$ok   = 0;
$fail = 0;

function assert_test(string $label, bool $condition): void
{
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

// Langues
$langs = ConfigModel::getLangs();

assert_test('getLangs retourne un array',                 is_array($langs));
assert_test('getLangs contient au moins une langue',      count($langs) > 0);
assert_test('Première langue a une clé code',             isset($langs[0]['code']));
assert_test('Première langue a une clé label',            isset($langs[0]['label']));
assert_test('Code de la première langue est une string',  is_string($langs[0]['code']));

// Langue par défaut
$default = ConfigModel::getDefaultLang();

assert_test('getDefaultLang retourne une string',         is_string($default));
assert_test('getDefaultLang correspond au premier code',  $default === $langs[0]['code']);

// Titre
$title = ConfigModel::getTitle();

assert_test('getTitle retourne une string',               is_string($title));
assert_test('getTitle n\'est pas vide',                   !empty($title));

// Cache
ConfigModel::clearCache();
$langsAfterClear = ConfigModel::getLangs();

assert_test('clearCache recharge les langues',            $langsAfterClear === $langs);

// --- Résumé ---
echo "\n$ok passed, $fail failed\n";