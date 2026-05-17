<?php
/**
 * Page : Home
 * Rendu piloté par json/pages/home.json
 */
require_once ROOT_PATH . 'src/core/page_renderer.php';

$renderer = new PageRenderer(APP_LANG);
$renderer->render('accueil');