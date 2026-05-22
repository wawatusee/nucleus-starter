<?php
// Fragment admin — chargé par inc/main.php
// Gestion des galeries JSON

require_once ROOT_PATH . 'src/utils/json_handler.php';

// Liste des galeries existantes
$galleryFiles = [];
if (is_dir(JSON_GALLERIES_DIR)) {
    $files = array_diff(scandir(JSON_GALLERIES_DIR), ['..', '.']);
    foreach ($files as $file) {
        if (str_ends_with($file, '.json')) {
            $galleryFiles[] = str_replace('.json', '', $file);
        }
    }
}

// Répertoires d'images disponibles
$imageDirs = [];
if (is_dir(DIR_IMG_CONTENT)) {
    foreach (glob(DIR_IMG_CONTENT . '*', GLOB_ONLYDIR) as $dir) {
        $imageDirs[] = basename($dir);
    }
}

$langs     = ConfigModel::getLangs();
$langCodes = array_column($langs, 'code');
?>

<div class="admin-editor-container">

    <aside class="admin-sidebar">
        <h4>Galeries</h4>
        <ul id="gallery-list">
            <?php foreach ($galleryFiles as $folder): ?>
                <li class="sidebar-item" data-folder="<?= htmlspecialchars($folder) ?>">
                    <div class="item-main">
                        <a href="#" class="load-gallery-link" data-folder="<?= htmlspecialchars($folder) ?>">
                            <?= htmlspecialchars($folder) ?>
                        </a>
                    </div>
                    <div class="item-actions">
                        <button type="button" class="btn-delete-gallery btn-delete-file"
                            data-folder="<?= htmlspecialchars($folder) ?>"
                            title="Supprimer la galerie">🗑️</button>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <button type="button" id="btn-new-gallery" class="btn-secondary" style="width:100%; margin-top:15px;">
            + Nouvelle galerie
        </button>
    </aside>

    <main class="admin-content">

        <section class="form-section" id="gallery-editor" style="display:none;">
            <h4 id="gallery-editor-title">Éditeur de galerie</h4>

            <div class="field-group">
                <label>Répertoire d'images</label>
                <select id="gallery-folder">
                    <option value="">-- Choisir un répertoire --</option>
                    <?php foreach ($imageDirs as $dir): ?>
                        <option value="<?= htmlspecialchars($dir) ?>"><?= htmlspecialchars($dir) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field-group">
                <label>Titre de la galerie</label>
                <nav class="lang-tabs-container" id="gallery-lang-tabs">
                    <?php foreach ($langs as $i => $langue): ?>
                        <button type="button" class="tab-btn <?= $i === 0 ? 'active' : '' ?>"
                            data-lang="<?= htmlspecialchars($langue['code']) ?>"
                            onclick="switchGalleryEditorLang('<?= htmlspecialchars($langue['code']) ?>')">
                            <?= htmlspecialchars($langue['label']) ?>
                        </button>
                    <?php endforeach; ?>
                </nav>
                <?php foreach ($langs as $i => $langue): ?>
                    <div class="lang-field" data-lang="<?= htmlspecialchars($langue['code']) ?>"
                        style="display:<?= $i === 0 ? 'block' : 'none' ?>">
                        <input type="text" class="gallery-title-input"
                            data-lang="<?= htmlspecialchars($langue['code']) ?>"
                            placeholder="Titre (<?= htmlspecialchars($langue['code']) ?>)"
                            class="main-title-input">
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="field-group">
                <label>Images</label>
                <div id="gallery-image-list"></div>
                <button type="button" id="btn-add-image-row" class="btn-secondary">+ Ajouter une image</button>
            </div>

            <div class="editor-actions-bar">
                <button type="button" id="btn-save-gallery" class="btn-save">Enregistrer</button>
                <p id="gallery-save-status" class="field-hint"></p>
            </div>
        </section>

        <section class="form-section" id="gallery-placeholder">
            <p class="field-hint">Sélectionnez une galerie ou créez-en une nouvelle.</p>
        </section>

    </main>
</div>

<!-- Modale navigateur médias -->
<div id="gallery-media-browser"
    style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:8px; padding:24px; width:800px; max-width:95vw; max-height:85vh; display:flex; flex-direction:column; gap:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0;">Choisir une image</h3>
            <button type="button" id="gallery-browser-close"
                style="background:none; border:none; font-size:1.5rem; cursor:pointer;">×</button>
        </div>
        <div style="display:flex; gap:8px; align-items:center;">
            <label>Répertoire :</label>
            <select id="gallery-dir-select" style="flex:1;"></select>
        </div>
        <div id="gallery-media-grid"
            style="display:grid; grid-template-columns:repeat(auto-fill, minmax(120px, 1fr)); gap:12px; overflow-y:auto; flex:1;">
        </div>
    </div>
</div>

<script>
const API        = 'api/';
const LANG_CODES  = <?= json_encode($langCodes) ?>;
const LANG_LABELS = <?= json_encode(array_column($langs, 'label', 'code')) ?>;

let currentFolder = null;

// === LANGUE ===
function switchGalleryEditorLang(lang) {
    document.querySelectorAll('#gallery-lang-tabs .tab-btn').forEach(b =>
        b.classList.toggle('active', b.dataset.lang === lang)
    );
    document.querySelectorAll('#gallery-editor .lang-field').forEach(f =>
        f.style.display = f.dataset.lang === lang ? 'block' : 'none'
    );
}

// === TEMPLATE LIGNE IMAGE ===
function tplImageRow(img = {}) {
    const src     = img.src     || '';
    const alt     = img.alt     || {};
    const caption = img.caption || {};

    const langFields = LANG_CODES.map((lang, i) => `
        <div class="lang-field" data-lang="${lang}" style="display:${i === 0 ? 'block' : 'none'}">
            <input type="text" class="img-alt" data-lang="${lang}"
                placeholder="Alt (${lang})" value="${escapeHtml(alt[lang] || '')}">
            <input type="text" class="img-caption" data-lang="${lang}"
                placeholder="Légende (${lang}) — optionnel" value="${escapeHtml(caption[lang] || '')}">
        </div>
    `).join('');

    const tabBtns = LANG_CODES.map((lang, i) => `
        <button type="button" class="tab-btn ${i === 0 ? 'active' : ''}"
            data-lang="${lang}" onclick="switchImageRowLang(this)">
            ${LANG_LABELS[lang] || lang}
        </button>
    `).join('');

    const div = document.createElement('div');
    div.className = 'gallery-image-row';
    div.innerHTML = `
        <div class="gallery-image-row__src">
            <input type="text" class="img-src" placeholder="nom-fichier.jpg" value="${escapeHtml(src)}">
            <button type="button" class="btn-browse-gallery btn-secondary" title="Parcourir">Parcourir</button>
            <button type="button" class="btn-delete-image-row btn-delete-file" title="Supprimer">🗑️</button>
        </div>
        <div class="gallery-image-row__langs">
            <nav class="lang-tabs-container">${tabBtns}</nav>
            ${langFields}
        </div>
    `;
    return div;
}

function switchImageRowLang(btn) {
    const lang = btn.dataset.lang;
    const row  = btn.closest('.gallery-image-row');
    row.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b.dataset.lang === lang));
    row.querySelectorAll('.lang-field').forEach(f => f.style.display = f.dataset.lang === lang ? 'block' : 'none');
}

function escapeHtml(str) {
    if (typeof str !== 'string') return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

// === CHARGEMENT ===
async function loadGallery(folder) {
    try {
        const res    = await fetch(`${API}get_gallery.php?folder=${encodeURIComponent(folder)}`);
        const result = await res.json();

        currentFolder = folder;
        document.getElementById('gallery-editor-title').textContent = folder;
        document.getElementById('gallery-folder').value = result.folder || folder;

        // Titres
        document.querySelectorAll('.gallery-title-input').forEach(input => {
            input.value = result.title?.[input.dataset.lang] || '';
        });

        // Images
        const list = document.getElementById('gallery-image-list');
        list.innerHTML = '';
        (result.images || []).forEach(img => list.appendChild(tplImageRow(img)));

        showEditor();
    } catch (e) {
        alert('Erreur chargement : ' + e.message);
    }
}

function showEditor() {
    document.getElementById('gallery-placeholder').style.display = 'none';
    document.getElementById('gallery-editor').style.display      = 'block';
}

function resetEditor() {
    currentFolder = null;
    document.getElementById('gallery-editor-title').textContent = 'Nouvelle galerie';
    document.getElementById('gallery-folder').value             = '';
    document.querySelectorAll('.gallery-title-input').forEach(i => i.value = '');
    document.getElementById('gallery-image-list').innerHTML     = '';
    showEditor();
}

// === COLLECTE ===
function collectGallery() {
    const folder = document.getElementById('gallery-folder').value.trim();
    const title  = {};

    document.querySelectorAll('.gallery-title-input').forEach(input => {
        title[input.dataset.lang] = input.value.trim();
    });

    const images = [];
    document.querySelectorAll('#gallery-image-list .gallery-image-row').forEach(row => {
        const src     = row.querySelector('.img-src')?.value.trim() || '';
        const alt     = {};
        const caption = {};
        row.querySelectorAll('.img-alt').forEach(i => alt[i.dataset.lang] = i.value.trim());
        row.querySelectorAll('.img-caption').forEach(i => caption[i.dataset.lang] = i.value.trim());
        if (src) images.push({ src, alt, caption });
    });

    return { folder, title, images };
}

// === SAUVEGARDE ===
document.getElementById('btn-save-gallery').addEventListener('click', async () => {
    const status  = document.getElementById('gallery-save-status');
    const payload = collectGallery();

    if (!payload.folder) {
        status.textContent = 'Erreur : répertoire manquant';
        return;
    }

    status.textContent = 'Enregistrement...';

    try {
        const res    = await fetch(`${API}save_gallery.php`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload)
        });
        const result = await res.json();

        if (result.success) {
            status.textContent = 'Enregistré ✓';
            setTimeout(() => status.textContent = '', 3000);

            // Ajouter dans la sidebar si nouvelle galerie
            if (!document.querySelector(`[data-folder="${payload.folder}"]`)) {
                addToSidebar(payload.folder);
            }
            currentFolder = payload.folder;
        } else {
            status.textContent = 'Erreur : ' + (result.errors?.join(', ') || result.error);
        }
    } catch (e) {
        status.textContent = 'Erreur : ' + e.message;
    }
});

function addToSidebar(folder) {
    const ul = document.getElementById('gallery-list');
    const li = document.createElement('li');
    li.className = 'sidebar-item';
    li.dataset.folder = folder;
    li.innerHTML = `
        <div class="item-main">
            <a href="#" class="load-gallery-link" data-folder="${folder}">${folder}</a>
        </div>
        <div class="item-actions">
            <button type="button" class="btn-delete-gallery btn-delete-file"
                data-folder="${folder}" title="Supprimer">🗑️</button>
        </div>
    `;
    ul.appendChild(li);
}

// === SUPPRESSION ===
async function deleteGallery(folder, liEl) {
    if (!confirm(`Supprimer la galerie "${folder}" ?`)) return;

    try {
        // Suppression du fichier JSON galerie
        const res    = await fetch(`${API}delete_gallery.php`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ folder })
        });
        const result = await res.json();

        if (result.success) {
            liEl?.remove();
            if (currentFolder === folder) {
                document.getElementById('gallery-editor').style.display      = 'none';
                document.getElementById('gallery-placeholder').style.display = 'block';
                currentFolder = null;
            }
        } else {
            alert('Erreur : ' + (result.error || 'inconnue'));
        }
    } catch (e) {
        alert('Erreur : ' + e.message);
    }
}

// === ÉVÉNEMENTS DÉLÉGUÉS ===
document.getElementById('gallery-list').addEventListener('click', (e) => {
    const link = e.target.closest('.load-gallery-link');
    if (link) { e.preventDefault(); loadGallery(link.dataset.folder); }

    const btnDelete = e.target.closest('.btn-delete-gallery');
    if (btnDelete) { e.preventDefault(); deleteGallery(btnDelete.dataset.folder, btnDelete.closest('li')); }
});

document.getElementById('btn-new-gallery').addEventListener('click', resetEditor);

document.getElementById('btn-add-image-row').addEventListener('click', () => {
    document.getElementById('gallery-image-list').appendChild(tplImageRow());
});

document.getElementById('gallery-image-list').addEventListener('click', (e) => {
    if (e.target.classList.contains('btn-delete-image-row')) {
        e.target.closest('.gallery-image-row').remove();
    }
    // Parcourir
    const btnBrowse = e.target.closest('.btn-browse-gallery');
    if (btnBrowse) {
        window._galleryTargetInput = btnBrowse.closest('.gallery-image-row').querySelector('.img-src');
        const modal = document.getElementById('gallery-media-browser');
        modal.style.display = 'flex';
        loadGalleryMediaDirs();
    }
});

// === MODALE MÉDIAS ===
document.getElementById('gallery-browser-close').addEventListener('click', () => {
    document.getElementById('gallery-media-browser').style.display = 'none';
});

async function loadGalleryMediaDirs() {
    try {
        const res  = await fetch(`${API}list_images.php`);
        const data = await res.json();
        if (!data.success) return;

        const select = document.getElementById('gallery-dir-select');
        select.innerHTML = '';
        data.dirs.forEach(dir => {
            const opt = document.createElement('option');
            opt.value = dir;
            opt.textContent = dir;
            select.appendChild(opt);
        });

        if (data.dirs.length) await loadGalleryMediaImages(data.dirs[0]);
    } catch (e) {
        console.error('Erreur chargement répertoires :', e);
    }
}

async function loadGalleryMediaImages(dir) {
    const grid = document.getElementById('gallery-media-grid');
    grid.innerHTML = '<p>Chargement...</p>';

    try {
        const res  = await fetch(`${API}list_images.php?dir=${encodeURIComponent(dir)}`);
        const data = await res.json();

        if (!data.success || !data.images.length) {
            grid.innerHTML = '<p>Aucune image.</p>';
            return;
        }

        grid.innerHTML = '';
        data.images.forEach(filename => {
            const fig = document.createElement('figure');
            fig.style.cssText = 'margin:0; cursor:pointer; border:2px solid transparent; border-radius:4px; overflow:hidden;';
            fig.innerHTML = `
                <img src="/public/img/content/${dir}/thumbs/${filename}" alt="${filename}"
                     style="width:100%; height:90px; object-fit:cover; display:block;">
                <figcaption style="font-size:0.7rem; padding:4px; text-align:center;
                    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    ${filename}
                </figcaption>`;

            fig.addEventListener('mouseenter', () => fig.style.borderColor = '#3b5bdb');
            fig.addEventListener('mouseleave', () => fig.style.borderColor = 'transparent');
            fig.addEventListener('click', () => {
                if (window._galleryTargetInput) {
                    window._galleryTargetInput.value = filename;
                }
                document.getElementById('gallery-media-browser').style.display = 'none';
            });

            grid.appendChild(fig);
        });
    } catch (e) {
        grid.innerHTML = '<p>Erreur de chargement.</p>';
    }
}

document.getElementById('gallery-dir-select').addEventListener('change', (e) => {
    loadGalleryMediaImages(e.target.value);
});
</script>
