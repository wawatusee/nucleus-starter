<?php
// sécurité si tu veux plus tard
// if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
?>

<section class="form-contener">
    <h2>Tableau de bord</h2>

    <p>Bienvenue dans l’interface d’administration.</p>

    <ul>
        <li>
            <a href="index.php?page=pages">➡ Gérer les pages</a>
        </li>
        <li>
            <a href="index.php?page=articles">➡ Gérer les articles</a>
        </li>
                <li>
            <a href="index.php?page=medias">Gérer les medias</a>
        </li>
        <li>
            <a href="index.php?page=menus">Gérer les menus</a>
        </li>
    </ul>

</section>
