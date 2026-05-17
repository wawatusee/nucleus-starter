<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- OG / Réseaux sociaux — TODO: alimenter depuis JSON page/article -->
    <meta property="og:title"       content="">
    <meta property="og:description" content="">
    <meta property="og:type"        content="">
    <meta property="og:url"         content="">
    <meta property="og:image"       content="">
    <meta property="og:site_name"   content="<?= htmlspecialchars($str_titleWebSite) ?>">

    <!-- CSS génériques -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/footer.css">

    <!-- CSS spécifique à la page -->
    <?php
    $cssFile    = "css/pages/{$page}.css";
    $cssFileAbs = ROOT_PATH . "public/{$cssFile}";
    if (file_exists($cssFileAbs)) {
        echo '<link rel="stylesheet" href="' . $cssFile . '">';
    }
    ?>

    <!-- JS générique -->
    <script src="js/menu.js" defer></script>

    <!-- JS spécifique à la page -->
    <?php
    $jsFile    = "js/pages/{$page}.js";
    $jsFileAbs = ROOT_PATH . "public/{$jsFile}";
    if (file_exists($jsFileAbs)) {
        echo '<script src="' . $jsFile . '" defer></script>';
    }
    ?>

    <link rel="shortcut icon" type="image/png" href="favicon.ico">
    <title><?= htmlspecialchars($str_titleWebSite) ?></title>
</head>