
<header>
    <h1>Admin des articles et des pages</h1>
    <nav>
        <ul style="display:flex; gap:1rem; list-style:none;">
            <?php foreach(ADMIN_PAGES as $p): ?>
                <li>
                    <a href="index.php?page=<?= $p ?>"><?= ucfirst($p) ?></a>
                </li>
            <?php endforeach; ?>
            <li>
                <a href="index.php?logout=1">Se déconnecter</a>
            </li>
        </ul>
    </nav>
</header>
