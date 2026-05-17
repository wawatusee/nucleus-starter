<main>
<?php
/*
 * Contrôleur central — chargement de la page courante.
 *
 * Variables disponibles, préparées dans public/index.php :
 *   $page          — slug de la page courante (ex: "home")
 *                    validé par whitelist PAGE_ARRAY, sanitisé par htmlspecialchars()
 *                    fallback sur $pagesDuMenus[0] si absent ou invalide
 *   $pagesDuMenus  — array des slugs dans l'ordre du Main_menu (depuis menus.json)
 *   PAGE_ARRAY     — constante identique à $pagesDuMenus, sert de whitelist de sécurité
 */
if (in_array($page, PAGE_ARRAY)) {
    require_once __DIR__ . '/pages/' . $page . '.php';
} else {
    require_once __DIR__ . '/pages/404.php';
}
?>
</main>