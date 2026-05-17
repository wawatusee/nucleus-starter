<?php
require_once "../config/config.php";

// =========================================================
// LANGUE
// =========================================================
if (isset($_GET['lang']) && in_array($_GET['lang'], array_column($langs, 'code'))) {
    $lang = $_GET['lang'];
} else {
    $lang = LANG_DEFAULT;
}
define('APP_LANG', $lang);

// =========================================================
// PAGE
// =========================================================
$defaultPage = $pagesDuMenus[0];
if (isset($_GET['page']) && in_array($_GET['page'], PAGE_ARRAY)) {
    $page = htmlspecialchars($_GET['page']);
} else {
    $page = $defaultPage;
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(APP_LANG, ENT_QUOTES, 'UTF-8') ?>" prefix="og:http://ogp.me/ns#">
    <?php require_once "../inc/head.php"; ?>
    <body>
        <?php
        require_once "../inc/header.php";
        require_once "../inc/main.php";
        require_once "../inc/footer.php";
        ?>
    </body>
</html>