<?php
// Fragment admin — chargé par inc/main.php
// Éditeur de layouts de pages

$targetDir = JSON_PAGES_DIR;
$galleryFolders = [];
if (is_dir(JSON_GALLERIES_DIR)) {
    $files = array_diff(scandir(JSON_GALLERIES_DIR), ['..', '.']);
    foreach ($files as $file) {
        if (str_ends_with($file, '.json')) {
            $galleryFolders[] = str_replace('.json', '', $file);
        }
    }
}

$files = [];
if (is_dir($targetDir)) {
    $files = array_diff(scandir($targetDir), ['..', '.']);
}
?>

<div class="admin-editor-container">
    <aside class="admin-sidebar">
        <h4>Pages du site (Layout)</h4>
        <ul id="file-list">
            <?php foreach ($files as $file):
                if (!str_contains($file, '.json'))
                    continue;
                $pageId = str_replace('.json', '', $file);
                $hasPhp = file_exists(ROOT_PATH . 'inc/pages/' . $pageId . '.php');
                ?>
                <li class="sidebar-item">
                    <div class="item-main">
                        <a href="#" class="load-page-link" data-filename="<?= $file ?>">
                            <?= htmlspecialchars($pageId) ?>
                        </a>
                    </div>
                    <div class="item-actions">
                        <?php if (!$hasPhp): ?>
                            <button type="button" class="btn-create-php btn-secondary"
                                data-pageid="<?= htmlspecialchars($pageId) ?>"
                                title="Créer inc/pages/<?= htmlspecialchars($pageId) ?>.php">
                                📄
                            </button>
                        <?php else: ?>
                            <span class="php-exists" title="inc/pages/<?= htmlspecialchars($pageId) ?>.php existe">✓</span>
                        <?php endif; ?>
                        <button type="button" class="btn-delete-file" data-filename="<?= $file ?>"
                            title="Supprimer le layout">
                            🗑️
                        </button>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <button id="btn-new-page" class="btn-secondary" style="width:100%; margin-top:15px;">
            + Nouveau Layout Page
        </button>
    </aside>

    <section id="builder-workspace">
        <input type="text" id="page-title" class="main-title-input" placeholder="Nom de la page (ex: contact)">
        <p class="id-preview-container">Fichier : <span id="generated-filename">nouveau.json</span></p>

        <div id="page-blocks-container"></div>

        <div class="editor-actions-bar">
            <div class="add-block-controls">
                <select id="select-block-type">
                    <option value="article_ref">Insérer un Article (JSON)</option>
                    <option value="gallery_ref">Insérer une Galerie Photo</option>
                    <option value="ui_component">Composant UI (Hero, Form...)</option>
                </select>
                <button id="btn-add-block" class="btn-primary">+ Ajouter</button>
            </div>
            <button id="btn-save-page" class="btn-success">Enregistrer le Layout</button>
        </div>
    </section>
</div>
<script src="js/page_builder.js"></script>
<script>
    window.availableGalleries = <?= json_encode($galleryFolders) ?>;
    window.LANG_CODES = <?= json_encode($langCodes) ?>;
    window.LANG_LABELS = <?= json_encode(array_column(ConfigModel::getLangs(), 'label', 'code')) ?>;

    // === CRÉATION FICHIER PHP ===
    document.getElementById('file-list').addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-create-php');
        if (!btn) return;

        const pageId = btn.dataset.pageid;
        if (!confirm(`Créer inc/pages/${pageId}.php ?`)) return;

        try {
            const res = await fetch('api/create_page_file.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: pageId })
            });
            const result = await res.json();

            if (result.success) {
                btn.replaceWith(Object.assign(
                    document.createElement('span'),
                    { className: 'php-exists', title: `${result.file} créé`, textContent: '✓' }
                ));
            } else {
                alert('Erreur : ' + (result.error || 'inconnue'));
            }
        } catch (e) {
            alert('Erreur : ' + e.message);
        }
    });
</script>