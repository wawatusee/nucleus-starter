/**
 * PageEditor - Architecture Data-Driven
 */
class PageEditor {
    constructor() {
        // 1. Le "Store"
        this.resources = {
            articles:     [],
            galleries:    window.availableGalleries || [],
            galleryJsons: []
        };

        // Langues injectées depuis PHP
        this.langCodes  = window.LANG_CODES  || ['fr'];
        this.langLabels = window.LANG_LABELS || { fr: 'Français' };

        // 2. Le Registre des Blocs
        this.blockRegistry = {
            article_ref: {
                title: "📄 Section : Article",
                render: (id, data) => this.tplSelect(id, 'article_ref', 'data-filename',
                    this.resources.articles.map(a => ({ id: a.filename || a, name: (a.id || a).replace('.json', '') })),
                    data.filename, "-- Sélectionner un article --"),
                parse: (el) => ({ filename: el.querySelector('.data-filename').value })
            },
            gallery_ref: {
                title: "🖼️ Galerie Photo",
                render: (id, data) => {
                    const folderOptions = this.resources.galleries.map(g =>
                        `<option value="${g}" ${data.folder === g ? 'selected' : ''}>${g}</option>`
                    ).join('');

                    const galleryOptions = this.resources.galleryJsons
                        ? this.resources.galleryJsons.map(g =>
                            `<option value="${g}" ${data.gallery === g ? 'selected' : ''}>${g}</option>`
                          ).join('')
                        : '';

                    return `
                        <div class="block-item" data-id="${id}" data-type="gallery_ref">
                            <div class="block-header">
                                <strong>🖼️ Galerie Photo</strong>
                                <button class="btn-delete-block">×</button>
                            </div>
                            <div class="block-body">
                                <div class="field-group">
                                    <label>Répertoire d'images</label>
                                    <select class="data-folder">
                                        <option value="">-- Choisir un répertoire --</option>
                                        ${folderOptions}
                                    </select>
                                </div>
                                <div class="field-group">
                                    <label>Galerie JSON <span style="color:#868e96; font-size:.78rem;">(optionnel — rendu riche)</span></label>
                                    <select class="data-gallery">
                                        <option value="">-- Rendu simple --</option>
                                        ${galleryOptions}
                                    </select>
                                </div>
                                <div class="gallery-preview" style="margin-top:8px;"></div>
                            </div>
                        </div>`;
                },
                parse: (el) => {
                    const folder  = el.querySelector('.data-folder')?.value  || '';
                    const gallery = el.querySelector('.data-gallery')?.value || '';
                    const result  = { folder };
                    if (gallery) result.gallery = gallery;
                    return result;
                }
            },
            ui_component: {
                title: "⚙️ Composant UI",
                render: (id, data) => this.tplSelect(id, 'ui_component', 'data-comp-name', [
                    { id: 'hero',    name: 'Bannière Hero' },
                    { id: 'contact', name: 'Formulaire Contact' },
                    { id: 'gallery', name: 'Grille Galerie' }
                ], data.name, null),
                parse: (el) => ({ name: el.querySelector('.data-comp-name').value })
            }
        };

        this.currentFilename = null;
        this.initEventListeners();
    }

    // =========================================================
    // TEMPLATES
    // =========================================================

    tplSelect(id, type, className, list, selected, placeholder) {
        const options = list.map(item =>
            `<option value="${item.id}" ${selected === item.id ? 'selected' : ''}>${item.name}</option>`
        ).join('');

        const isGallery = type === 'gallery_ref';

        return `
            <div class="block-item" data-id="${id}" data-type="${type}">
                <div class="block-header">
                    <strong>${this.blockRegistry[type].title}</strong>
                    <button class="btn-delete-block">×</button>
                </div>
                <div class="block-body">
                    <select class="${className}">
                        ${placeholder ? `<option value="">${placeholder}</option>` : ''}
                        ${options}
                    </select>
                    ${isGallery ? `<div class="gallery-preview" style="margin-top:8px;"></div>` : ''}
                </div>
            </div>`;
    }

    tplGallery(id, data = {}) {
        const folder      = data.folder || '';
        const title       = data.title  || {};
        const images      = data.images || [];

        // Onglets langue pour le titre
        const titleLangTabs = this.langCodes.map((lang, i) => `
            <button type="button" class="tab-btn ${i === 0 ? 'active' : ''}"
                data-lang="${lang}" onclick="editor.switchGalleryLang(this, '${id}')">
                ${this.langLabels[lang] || lang}
            </button>
        `).join('');

        const titleLangFields = this.langCodes.map((lang, i) => `
            <div class="lang-field" data-lang="${lang}" style="display:${i === 0 ? 'block' : 'none'}">
                <input type="text" class="gallery-title" data-lang="${lang}"
                    placeholder="Titre (${lang})"
                    value="${this.escapeHtml(title[lang] || '')}">
            </div>
        `).join('');

        // Images existantes
        const imageRows = images.map((img, i) => this.tplImageRow(img, i)).join('');

        // Options de sélection du dossier
        const folderOptions = this.resources.galleries.map(g =>
            `<option value="${g}" ${folder === g ? 'selected' : ''}>${g}</option>`
        ).join('');

        return `
            <div class="block-item block-item--gallery" data-id="${id}" data-type="gallery_ref">
                <div class="block-header">
                    <strong>🖼️ Galerie Photo</strong>
                    <button class="btn-delete-block">×</button>
                </div>
                <div class="block-body">
                    <div class="field-group">
                        <label>Répertoire</label>
                        <select class="gallery-folder">
                            <option value="">-- Choisir un répertoire --</option>
                            ${folderOptions}
                        </select>
                    </div>
                    <div class="field-group">
                        <label>Titre de la galerie</label>
                        <nav class="lang-tabs-container">${titleLangTabs}</nav>
                        ${titleLangFields}
                    </div>
                    <div class="gallery-images">
                        <label>Images</label>
                        <div class="gallery-image-list">${imageRows}</div>
                        <button type="button" class="btn-add-image btn-secondary">+ Ajouter une image</button>
                    </div>
                </div>
            </div>`;
    }

    tplImageRow(img = {}, index = null) {
        const src     = img.src     || '';
        const alt     = img.alt     || {};
        const caption = img.caption || {};

        const altFields = this.langCodes.map((lang, i) => `
            <div class="lang-field" data-lang="${lang}" style="display:${i === 0 ? 'block' : 'none'}">
                <input type="text" class="img-alt" data-lang="${lang}"
                    placeholder="Alt (${lang})"
                    value="${this.escapeHtml(alt[lang] || '')}">
                <input type="text" class="img-caption" data-lang="${lang}"
                    placeholder="Légende (${lang}) — optionnel"
                    value="${this.escapeHtml(caption[lang] || '')}">
            </div>
        `).join('');

        return `
            <div class="gallery-image-row">
                <div class="gallery-image-row__src">
                    <input type="text" class="img-src" placeholder="nom-fichier.jpg"
                        value="${this.escapeHtml(src)}">
                    <button type="button" class="btn-delete-image-row btn-delete-file" title="Supprimer">🗑️</button>
                </div>
                <div class="gallery-image-row__langs">
                    ${altFields}
                </div>
            </div>`;
    }

    // =========================================================
    // PARSE GALLERY
    // =========================================================

    parseGallery(el) {
        const folder = el.querySelector('.gallery-folder')?.value || '';
        const title  = {};

        el.querySelectorAll('.gallery-title').forEach(input => {
            title[input.dataset.lang] = input.value.trim();
        });

        const images = [];
        el.querySelectorAll('.gallery-image-row').forEach(row => {
            const src     = row.querySelector('.img-src')?.value.trim() || '';
            const alt     = {};
            const caption = {};

            row.querySelectorAll('.img-alt').forEach(input => {
                alt[input.dataset.lang] = input.value.trim();
            });
            row.querySelectorAll('.img-caption').forEach(input => {
                caption[input.dataset.lang] = input.value.trim();
            });

            if (src) images.push({ src, alt, caption });
        });

        return { folder, title, images };
    }

    // =========================================================
    // UTILITAIRES
    // =========================================================

    escapeHtml(str) {
        if (typeof str !== 'string') return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    switchGalleryLang(btn, blockId) {
        const lang  = btn.dataset.lang;
        const block = document.querySelector(`.block-item[data-id="${blockId}"]`);
        if (!block) return;

        block.querySelectorAll('.tab-btn').forEach(b =>
            b.classList.toggle('active', b.dataset.lang === lang)
        );
        block.querySelectorAll('.lang-field').forEach(f =>
            f.style.display = f.dataset.lang === lang ? 'block' : 'none'
        );
    }

    // =========================================================
    // PRÉVISUALISATION GALERIE
    // =========================================================

    async loadGalleryPreview(selectEl) {
        const blockBody = selectEl.closest('.block-body');
        const preview   = blockBody?.querySelector('.gallery-preview');
        if (!preview) return;

        const folder  = blockBody.querySelector('.data-folder')?.value  || '';
        const gallery = blockBody.querySelector('.data-gallery')?.value || '';

        if (!folder) {
            preview.innerHTML = '';
            return;
        }

        // Mode simple — pas de JSON galerie
        if (!gallery) {
            preview.innerHTML = `
                <div style="background:#f8f9fa; border:1px solid #e2e4e8; border-radius:4px; padding:10px 12px; font-size:.82rem; color:#868e96;">
                    Rendu simple — toutes les images de <strong>${this.escapeHtml(folder)}/</strong>
                </div>`;
            return;
        }

        preview.innerHTML = '<p style="font-size:.8rem; color:#868e96;">Chargement...</p>';

        try {
            const res    = await fetch(`api/get_gallery.php?folder=${encodeURIComponent(gallery)}`);
            const result = await res.json();

            if (result.success === false) {
                preview.innerHTML = `<p style="font-size:.8rem; color:#e03131;">Galerie JSON introuvable</p>`;
                return;
            }

            const title = result.title?.[this.langCodes[0]] || gallery;
            const count = result.images?.length || 0;

            preview.innerHTML = `
                <div style="background:#edf2ff; border:1px solid #bac8ff; border-radius:4px; padding:10px 12px; font-size:.82rem;">
                    <strong>${this.escapeHtml(title)}</strong>
                    <span style="color:#868e96; margin-left:8px;">${count} image${count > 1 ? 's' : ''}</span>
                    <a href="?page=galleries" style="display:block; margin-top:6px; color:#3b5bdb; text-decoration:none; font-size:.78rem;">
                        ✏️ Éditer cette galerie →
                    </a>
                </div>`;
        } catch (e) {
            preview.innerHTML = `<p style="font-size:.8rem; color:#e03131;">Erreur : ${e.message}</p>`;
        }
    }

    // =========================================================
    // ÉVÉNEMENTS
    // =========================================================

    initEventListeners() {
        const container = document.getElementById('page-blocks-container');

        container.addEventListener('click', (e) => {
            // Suppression bloc
            if (e.target.classList.contains('btn-delete-block')) {
                e.target.closest('.block-item').remove();
            }
            // Suppression ligne image
            if (e.target.classList.contains('btn-delete-image-row')) {
                e.target.closest('.gallery-image-row').remove();
            }
            // Ajout ligne image
            if (e.target.classList.contains('btn-add-image')) {
                const list = e.target.closest('.block-body').querySelector('.gallery-image-list');
                list.insertAdjacentHTML('beforeend', this.tplImageRow());
            }
        });

        // Prévisualisation galerie au changement de sélection
        container.addEventListener('change', (e) => {
            if (e.target.classList.contains('data-folder') ||
                e.target.classList.contains('data-gallery')) {
                this.loadGalleryPreview(e.target);
            }
        });

        document.getElementById('btn-add-block').addEventListener('click', () => {
            const type = document.getElementById('select-block-type').value;
            this.addBlock(type);
        });

        document.getElementById('btn-save-page').addEventListener('click', () => this.savePage());

        const btnNew = document.getElementById('btn-new-page');
        if (btnNew) btnNew.addEventListener('click', () => this.resetEditor());

        const fileList = document.getElementById('file-list');
        if (fileList) {
            fileList.addEventListener('click', (e) => {
                const link = e.target.closest('.load-page-link');
                if (link) {
                    e.preventDefault();
                    this.loadPageLayout(link.dataset.filename);
                    return;
                }
                const btnDelete = e.target.closest('.btn-delete-file');
                if (btnDelete && btnDelete.dataset.filename) {
                    e.preventDefault();
                    this.deletePage(btnDelete.dataset.filename, btnDelete.closest('li'));
                }
            });
        }

        document.getElementById('page-title').addEventListener('input', (e) => {
            if (!this.currentFilename) {
                const slug = this.slugify(e.target.value);
                document.getElementById('generated-filename').textContent = slug ? slug + '.json' : 'nouveau.json';
            }
        });
    }

    // =========================================================
    // ACTIONS
    // =========================================================

    async loadResources() {
        try {
            const [artRes, galRes] = await Promise.all([
                fetch('api/list_articles.php?meta=1'),
                fetch('api/list_galleries.php')
            ]);
            const articles  = await artRes.json();
            const galleries = await galRes.json();
            this.resources.articles     = Array.isArray(articles)          ? articles          : [];
            this.resources.galleries    = galleries.success ? galleries.galleries : [];
            this.resources.galleryJsons = galleries.success ? galleries.galleries : [];
        } catch (e) {
            console.error("Erreur chargement ressources :", e);
        }
    }

    addBlock(type, data = {}) {
        if (!this.blockRegistry[type]) return;
        const id   = 'block_' + Date.now();
        const html = this.blockRegistry[type].render(id, data);
        document.getElementById('page-blocks-container').insertAdjacentHTML('beforeend', html);
    }

    async loadPageLayout(filename) {
        try {
            const res = await fetch(`api/get_page.php?file=${encodeURIComponent(filename)}`);
            if (!res.ok) throw new Error("Réponse serveur invalide");

            const data = await res.json();
            if (data.success === false) throw new Error(data.error || "Erreur inconnue");

            this.currentFilename = filename;
            document.getElementById('page-title').value               = data.meta?.id || filename.replace('.json', '');
            document.getElementById('generated-filename').textContent = filename;
            document.getElementById('page-blocks-container').innerHTML = '';

            if (Array.isArray(data.layout)) {
                data.layout.forEach(blockData => {
                    this.addBlock(blockData.type, blockData);
                    // Prévisualisation immédiate pour les gallery_ref
                    if (blockData.type === 'gallery_ref' && blockData.folder) {
                        const container = document.getElementById('page-blocks-container');
                        const lastBlock = container.lastElementChild;
                        const select    = lastBlock?.querySelector('.data-folder');
                        if (select) this.loadGalleryPreview(select);
                    }
                });
            }
        } catch (e) {
            console.error("Erreur de chargement :", e);
            alert("Erreur lors du chargement du layout : " + e.message);
        }
    }

    async savePage() {
        const titleInput = document.getElementById('page-title').value.trim();
        if (!titleInput) {
            alert("Veuillez saisir un nom de page.");
            return;
        }

        const id = this.currentFilename
            ? this.currentFilename.replace('.json', '')
            : this.slugify(titleInput);

        const layout = [];

        document.querySelectorAll('.block-item').forEach(el => {
            const type = el.dataset.type;
            if (!this.blockRegistry[type]) return;
            const parsed = this.blockRegistry[type].parse(el);
            layout.push({ type, ...parsed });
        });

        const payload = {
            type: 'page',
            meta: { id, status: 'draft' },
            layout
        };

        try {
            const res    = await fetch('api/save_page.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload)
            });
            const result = await res.json();

            if (result.success) {
                this.currentFilename = result.filename;
                document.getElementById('generated-filename').textContent = result.filename;
                alert('Page enregistrée avec succès !');
                if (!document.querySelector(`.load-page-link[data-filename="${result.filename}"]`)) {
                    location.reload();
                }
            } else {
                alert('Erreurs :\n' + (result.errors || [result.error]).join('\n'));
            }
        } catch (e) {
            console.error("Erreur de sauvegarde :", e);
            alert("Erreur lors de la sauvegarde.");
        }
    }

    async deletePage(filename, liElement) {
        if (!confirm(`Supprimer la page "${filename}" ?`)) return;

        try {
            const res    = await fetch('api/delete_page.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ filename })
            });
            const result = await res.json();

            if (result.success) {
                liElement?.remove();
                if (this.currentFilename === filename) this.resetEditor();
            } else {
                alert('Erreur suppression : ' + (result.errors || [result.error]).join('\n'));
            }
        } catch (e) {
            console.error("Erreur suppression :", e);
            alert("Erreur lors de la suppression.");
        }
    }

    resetEditor() {
        this.currentFilename = null;
        document.getElementById('page-title').value               = '';
        document.getElementById('generated-filename').textContent = 'nouveau.json';
        document.getElementById('page-blocks-container').innerHTML = '';
    }

    slugify(str) {
        return str.toLowerCase().trim()
            .replace(/[àáâãäå]/g, 'a').replace(/[èéêë]/g, 'e')
            .replace(/[ìíîï]/g, 'i').replace(/[òóôõö]/g, 'o')
            .replace(/[ùúûü]/g, 'u').replace(/[ç]/g, 'c')
            .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }
}

// Initialisation
const editor = new PageEditor();
editor.loadResources();
