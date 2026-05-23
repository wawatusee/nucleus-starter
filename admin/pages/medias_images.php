<?php
// Fragment admin — chargé par inc/main.php
// Gestion des images d'un répertoire de médias

$dir = basename($_GET['dir'] ?? '');

if ($dir === '' || !is_dir(DIR_IMG_CONTENT . $dir)) {
    echo '<p class="admin-error">Répertoire introuvable.</p>';
    return;
}

$thumbsDir = DIR_IMG_CONTENT . $dir . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR;
$images = [];

if (is_dir($thumbsDir)) {
    $files = glob($thumbsDir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE);
    foreach ($files as $file) {
        $images[] = basename($file);
    }
}
?>

<div class="admin-editor-container">

    <aside class="admin-sidebar">
        <h4><?= htmlspecialchars($dir) ?></h4>
        <a href="?page=medias" class="btn-secondary" style="display:block; margin-bottom: 15px;">
            ← Répertoires
        </a>
        <ul id="image-list">
            <?php foreach ($images as $filename): ?>
                <li class="sidebar-item" data-filename="<?= htmlspecialchars($filename) ?>">
                    <div class="item-main">
                        <img src="<?= htmlspecialchars(PUBLIC_IMG_CONTENT . $dir) ?>/thumbs/<?= htmlspecialchars($filename) ?>"
                            alt="<?= htmlspecialchars($filename) ?>"
                            style="width: 48px; height: 48px; object-fit: cover; border-radius: 4px;">
                        <span><?= htmlspecialchars($filename) ?></span>
                    </div>
                    <div class="item-actions">
                        <button type="button" class="btn-rename-image" data-filename="<?= htmlspecialchars($filename) ?>"
                            title="Renommer">✏️</button>
                        <button type="button" class="btn-delete-image" data-filename="<?= htmlspecialchars($filename) ?>"
                            title="Supprimer">🗑️</button>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <main class="admin-content">

        <section class="form-section">
            <h4>Ajouter des images</h4>
            <input type="file" id="file-input" multiple accept="image/jpeg,image/png,image/webp">
            <button type="button" id="btn-upload" class="btn-primary">Uploader</button>
            <p id="upload-status" class="field-hint"></p>
        </section>

        <section class="form-section" id="rename-section" style="display:none;">
            <h4>Renommer</h4>
            <input type="text" id="rename-input" placeholder="Nouveau nom (sans extension)" class="main-title-input">
            <button type="button" id="btn-rename-confirm" class="btn-secondary">Confirmer</button>
            <button type="button" id="btn-rename-cancel" class="btn-secondary">Annuler</button>
        </section>

    </main>
</div>

<script>
    const DIR = <?= json_encode($dir) ?>;
    const PUBLIC_CONTENT = <?= json_encode(PUBLIC_IMG_CONTENT) ?>;
    const API = 'api/';
    let renamingFile = null;

    // === UPLOAD ===
    document.getElementById('btn-upload').addEventListener('click', async () => {
        const files = document.getElementById('file-input').files;
        if (!files.length) return;

        const status = document.getElementById('upload-status');
        status.textContent = 'Upload en cours...';

        for (const file of files) {
            const formData = new FormData();
            formData.append('image', file);
            formData.append('dir', DIR);

            try {
                const res = await fetch(API + 'upload_image.php', { method: 'POST', body: formData });
                const result = await res.json();

                if (result.success) {
                    addImageToList(result.base.split('/').pop() + '.' + result.ext);
                } else {
                    status.textContent = 'Erreur : ' + result.error;
                    return;
                }
            } catch (e) {
                status.textContent = 'Erreur : ' + e.message;
                return;
            }
        }

        status.textContent = files.length + ' image(s) uploadée(s).';
        document.getElementById('file-input').value = '';
    });

    // === SUPPRESSION ===
    document.getElementById('image-list').addEventListener('click', (e) => {
        const btnDelete = e.target.closest('.btn-delete-image');
        if (btnDelete) {
            const filename = btnDelete.dataset.filename;
            if (!confirm(`Supprimer "${filename}" ?`)) return;
            deleteImage(filename, btnDelete.closest('li'));
        }

        const btnRename = e.target.closest('.btn-rename-image');
        if (btnRename) {
            startRename(btnRename.dataset.filename);
        }
    });

    async function deleteImage(filename, liEl) {
        try {
            const res = await fetch(API + 'delete_image.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ dir: DIR, filename })
            });
            const result = await res.json();
            if (result.success) {
                liEl?.remove();
            } else {
                alert('Erreur : ' + result.error);
            }
        } catch (e) {
            alert('Erreur : ' + e.message);
        }
    }

    // === RENOMMAGE ===
    function startRename(filename) {
        renamingFile = filename;
        document.getElementById('rename-input').value = filename.replace('.jpg', '');
        document.getElementById('rename-section').style.display = 'block';
    }

    document.getElementById('btn-rename-cancel').addEventListener('click', () => {
        renamingFile = null;
        document.getElementById('rename-section').style.display = 'none';
    });

    document.getElementById('btn-rename-confirm').addEventListener('click', async () => {
        const newName = document.getElementById('rename-input').value.trim();
        if (!newName || !renamingFile) return;

        try {
            const res = await fetch(API + 'rename_image.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ dir: DIR, old_name: renamingFile, new_name: newName })
            });
            const result = await res.json();

            if (result.success) {
                const li = document.querySelector(`[data-filename="${renamingFile}"]`);
                if (li) {
                    li.dataset.filename = result.filename;
                    li.querySelector('.btn-delete-image').dataset.filename = result.filename;
                    li.querySelector('.btn-rename-image').dataset.filename = result.filename;
                    li.querySelector('span').textContent = result.filename;
                    li.querySelector('img').src = `${PUBLIC_CONTENT}${DIR}/thumbs/${filename}`;
                }
                renamingFile = null;
                document.getElementById('rename-section').style.display = 'none';
            } else {
                alert('Erreur : ' + result.error);
            }
        } catch (e) {
            alert('Erreur : ' + e.message);
        }
    });

    // === AJOUT DYNAMIQUE ===
    function addImageToList(filename) {
        const ul = document.getElementById('image-list');
        const li = document.createElement('li');
        li.className = 'sidebar-item';
        li.dataset.filename = filename;
        li.innerHTML = `
        <div class="item-main">
                <img src="${PUBLIC_CONTENT}${DIR}/thumbs/${filename}"
                 alt="${filename}"
                 style="width: 48px; height: 48px; object-fit: cover; border-radius: 4px;">
            <span>${filename}</span>
        </div>
        <div class="item-actions">
            <button type="button" class="btn-rename-image" data-filename="${filename}" title="Renommer">✏️</button>
            <button type="button" class="btn-delete-image" data-filename="${filename}" title="Supprimer">🗑️</button>
        </div>
    `;
        ul.appendChild(li);
    }
</script>