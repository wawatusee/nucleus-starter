<?php
// Redirection vers /public/ — point d'entrée unique du site
// Construit une URL absolue pour être compatible local et sous-dossier en ligne
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'];
$base     = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
header('Location: ' . $protocol . '://' . $host . $base . '/public/', true, 301);
exit;