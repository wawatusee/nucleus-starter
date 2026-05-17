<?php
require_once '../config/config.php';
require_once '../src/view/view_menus.php';

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

// --- Données de test ---
$menuArray = [
    (object)[
        'page'  => 'home',
        'titre' => (object)['fr' => 'Accueil', 'en' => 'Home']
    ],
    (object)[
        'page'  => 'contact',
        'titre' => (object)['fr' => 'Contact', 'en' => 'Contact']
    ],
];

// --- Tests ---

// Langue française, page courante = home
$view = new ViewMenu('fr', 'home');
$html = $view->getViewMainMenu($menuArray);

assert_test('Retourne une string',                        is_string($html));
assert_test('Contient le label fr "Accueil"',             str_contains($html, 'Accueil'));
assert_test('Lien home contient nav__link--active',       str_contains($html, 'href="?page=home') && str_contains($html, 'nav__link--active'));
assert_test('Lien contact ne contient pas active',        !str_contains($html, 'href="?page=contact') || !str_contains($html, 'nav__link--active'));
assert_test('Href contient lang=fr',                      str_contains($html, 'lang=fr'));

// Langue anglaise
$viewEn = new ViewMenu('en', 'contact');
$htmlEn = $viewEn->getViewMainMenu($menuArray);

assert_test('Retourne le label en "Home"',                str_contains($htmlEn, 'Home'));
assert_test('Lien contact actif en anglais',              str_contains($htmlEn, 'nav__link--active'));
assert_test('Href contient lang=en',                      str_contains($htmlEn, 'lang=en'));

// Fallback langue manquante
$menuFallback = [
    (object)[
        'page'  => 'events',
        'titre' => (object)['fr' => 'Événements']
    ],
];
$viewFallback = new ViewMenu('en', 'home');
$htmlFallback = $viewFallback->getViewMainMenu($menuFallback);

assert_test('Fallback fr si langue manquante',            str_contains($htmlFallback, 'Événements'));

// --- Résumé ---
echo "\n$ok passed, $fail failed\n";