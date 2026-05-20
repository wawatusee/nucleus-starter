<?php
// =========================================================
// SOCLE FRONT
// =========================================================
require_once realpath(__DIR__ . '/../config/config.php');

// =========================================================
// CHEMINS ADMIN
// =========================================================
define('ADMIN_PATH', __DIR__ . DIRECTORY_SEPARATOR);
define('JSON_PAGES_DIR', DIR_JSON . 'pages' . DIRECTORY_SEPARATOR);
define('JSON_ARTICLES_DIR', DIR_JSON . 'articles' . DIRECTORY_SEPARATOR);
define('GALLERIES_DIR', DIR_IMG_CONTENT . 'galleries' . DIRECTORY_SEPARATOR);

// =========================================================
// PAGES ACCESSIBLES (whitelist admin)
// =========================================================
define('ADMIN_PAGES', [
    'dashboard',
    'pages',
    'articles',
    'medias',
    'medias_images',
    'menus'
]);

// =========================================================
// UTILITAIRES
// =========================================================
require_once ROOT_PATH . 'src/utils/json_loader.php';

// =========================================================
// SESSION
// =========================================================
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =========================================================
// UPLOAD
// =========================================================
define('UPLOAD_MAX_SIZE', 2 * 1024 * 1024);
define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
