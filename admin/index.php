<?php
// admin/index.php
//session_start();
require_once __DIR__ . '/config_admin.php'; 

// --- sécurité admin simple ---
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// logout éventuel
if (isset($_GET['logout'])) {
    unset($_SESSION['user']);
    header("Location: login.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="fr">
<?php require_once __DIR__ . '/inc/head.php'; ?>

<body>
    <?php
    require_once __DIR__ . '/inc/header.php';
    require_once __DIR__ . '/inc/main.php';
    require_once __DIR__ . '/inc/footer.php'; ?>
</body>

</html>