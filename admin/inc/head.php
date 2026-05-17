<?php
if (isset($_GET['page'])) {
    // On nettoie pour ne garder que des caractères alphanumériques (sécurité)
    $page = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['page']);
    $cssFile = "css/pages/$page.css";

    if (file_exists(ADMIN_PATH . $cssFile)) {
        echo '<link rel="stylesheet" href="' . $cssFile . '">';
    }
}
?>

<head>
    <meta charset="UTF-8">
    <title>Administration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/admin.css">
</head>