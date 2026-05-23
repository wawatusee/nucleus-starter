<?php


// On récupère la liste des articles existants pour le menu latéral ou la gestion
$articlesDir = JSON_ARTICLES_DIR;
$existingArticles = array_diff(scandir($articlesDir), array('..', '.'));

?>
<?php echo '<!-- PUBLIC_IMG_CONTENT = ' . PUBLIC_IMG_CONTENT . ' -->'; ?>

<div class="admin-editor-container">
    <aside class="admin-sidebar">
        <h4>Articles existants</h4>
        <div class="sidebar-actions" style="margin-bottom: 20px;">
            <button type="button" id="new-article-btn" class="btn-primary" style="width: 100%;">
                + Nouvel Article
            </button>
        </div>
        <ul>
            <?php foreach ($existingArticles as $file): ?>
                <li class="sidebar-item">
                    <div class="item-main">
                        <a href="#" class="load-article-link" data-filename="<?= $file ?>">
                            <?= str_replace('.json', '', $file) ?>
                        </a>
                    </div>
                    <div class="item-actions">
                        <button type="button" class="btn-delete-file" data-filename="<?= $file ?>"
                            title="Supprimer définitivement">
                            🗑️
                        </button>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <main class="admin-content">

        <?php

        // Utilisation du modèle pour récupérer les langues configurées
        $langs = ConfigModel::getLangs();
        // Extraire code et label depuis la nouvelle structure
        $langKeys = array_column($langs, 'code');
        ?>
        <div class="lang-tabs-wrapper">
            <nav class="lang-tabs-container" id="editor-langs" data-config='<?= json_encode($langKeys) ?>'
                data-public-content='<?= htmlspecialchars(PUBLIC_IMG_CONTENT, ENT_QUOTES) ?>'

                <?php foreach ($langs as $langue): ?>
                    <button type="button" class="tab-btn <?= $langue['code'] === 'fr' ? 'active' : '' ?>"
                        data-lang="<?= $langue['code'] ?>" onclick="switchEditorLang('<?= $langue['code'] ?>')">
                        <?= htmlspecialchars($langue['label']) ?>
                    </button>
                <?php endforeach; ?>
            </nav>
        </div>

        <form id="article-builder">
            <section class="meta-section">
                <input type="text" id="article-title" placeholder="Titre de l'article..." class="main-title-input">
                <p class="id-preview">ID : <span id="generated-id">--</span></p>
            </section>

            <div id="blocks-workspace"></div>

            <div class="editor-actions-bar">
                <div class="add-block-controls">
                    <select id="new-block-type">
                        <option value="text">Paragraphe</option>
                        <option value="title">Titre (H2)</option>
                        <option value="list">Liste à puces</option>
                        <option value="link">Lien / Bouton</option>
                        <option value="image">Image</option>
                    </select>
                    <button type="button" id="add-block-trigger" class="btn-secondary">
                        + Ajouter un bloc
                    </button>
                </div>


                <div class="save-controls">
                    <button type="button" id="save-article-btn" class="btn-save">
                        Enregistrer l'Article
                    </button>
                </div>
            </div>
        </form>

    </main>
</div>
<!-- Modale navigateur médias — -->
<div id="media-browser"
    style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:1000; align-items:center; justify-content:center;">
    <div
        style="background:white; border-radius:8px; padding:24px; width:800px; max-width:95vw; max-height:85vh; display:flex; flex-direction:column; gap:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0;">Choisir une image</h3>
            <button type="button" id="media-browser-close"
                style="background:none; border:none; font-size:1.5rem; cursor:pointer;">×</button>
        </div>
        <div style="display:flex; gap:8px; align-items:center;">
            <label>Répertoire :</label>
            <select id="media-dir-select" style="flex:1;"></select>
        </div>
        <div id="media-grid"
            style="display:grid; grid-template-columns:repeat(auto-fill, minmax(120px, 1fr)); gap:12px; overflow-y:auto; flex:1;">
        </div>
    </div>
</div>
<script src="js/article_editor.js" defer></script>