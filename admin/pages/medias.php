<?php
// Fragment admin — chargé par inc/main.php
// Gestion des répertoires de médias

require_once ROOT_PATH . 'admin/src/folder_manager.class.php';

$fm = new FolderManager(DIR_IMG_CONTENT);

$error   = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? null;

    try {
        switch ($action) {

            case 'create':
                $name = trim($_POST['name'] ?? '');
                if ($name === '') throw new Exception("Le nom est requis.");
                $fm->create($name);
                $success = "Répertoire '{$name}' créé.";
                break;

            case 'rename':
                $old = trim($_POST['old_name'] ?? '');
                $new = trim($_POST['new_name'] ?? '');
                if ($old === '' || $new === '') throw new Exception("Les deux noms sont requis.");
                $fm->rename($old, $new);
                $success = "'{$old}' renommé en '{$new}'.";
                break;

            case 'delete':
                $name = trim($_POST['name'] ?? '');
                if ($name === '') throw new Exception("Le nom est requis.");
                $fm->delete($name);
                $success = "Répertoire '{$name}' supprimé.";
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$folders = $fm->list();
?>

<?php if ($error): ?>
    <p class="admin-error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($success): ?>
    <p class="admin-success"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>

<div class="admin-editor-container">

    <aside class="admin-sidebar">
        <h4>Répertoires</h4>
        <ul>
            <?php foreach ($folders as $folder): ?>
                <li class="sidebar-item">
                    <div class="item-main">
                        <?= htmlspecialchars($folder) ?>
                    </div>
                    <div class="item-actions">
                        <form method="post">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="name" value="<?= htmlspecialchars($folder) ?>">
                            <button type="submit" class="btn-delete-file"
                                onclick="return confirm('Supprimer <?= htmlspecialchars($folder) ?> et tout son contenu ?')"
                                title="Supprimer">🗑️</button>
                        </form>
                        <a href="?page=medias_images&dir=<?= urlencode($folder) ?>" class="btn-secondary">
                            Images
                        </a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <main class="admin-content">

        <section class="form-section">
            <h4>Créer un répertoire</h4>
            <form method="post">
                <input type="hidden" name="action" value="create">
                <input type="text" name="name" placeholder="Nom du répertoire" class="main-title-input">
                <button type="submit" class="btn-primary">Créer</button>
            </form>
        </section>

        <section class="form-section">
            <h4>Renommer un répertoire</h4>
            <form method="post">
                <input type="hidden" name="action" value="rename">
                <select name="old_name">
                    <?php foreach ($folders as $folder): ?>
                        <option value="<?= htmlspecialchars($folder) ?>"><?= htmlspecialchars($folder) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="new_name" placeholder="Nouveau nom" class="main-title-input">
                <button type="submit" class="btn-secondary">Renommer</button>
            </form>
        </section>

    </main>
</div>
