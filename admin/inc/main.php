<?php

$adminPages = ADMIN_PAGES;

$defaultPage = $adminPages[0];

$page = $_GET['page'] ?? $defaultPage;

// Sanitize
$page = basename($page);

// whitelist
if (!in_array($page, $adminPages)) {
    $page = $defaultPage;
}

// include page admin
require "pages/" . $page . ".php";
