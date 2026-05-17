/**
 * PageEditor - Architecture Data-Driven
 */
class PageEditor {
    constructor() {
        // 1. Le "Store" (Data Context)
        this.resources = {
            articles: [],
            galleries: window.availableGalleries || []
        };

        // 2. Le Registre des Blocs (Registry Pattern)
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
                render: (id, data) => this.tplSelect(id, 'gallery_ref', 'data-folder',
                    this.resources.galleries.map(g => ({ id: g, name: g })),
                    data.folder, "-- Choisir une galerie --"),
                parse: (el) => ({ folder: el.querySelector('.data-folder').value })
            },
            ui_component: {
                title: "⚙️ Composant UI",
                render: (id, data) => this.tplSelect(id, 'ui_component', 'data-comp-name', [
                    { id: 'hero', name: 'Bannière Hero' },
                    { id: 'contact', name: 'Formulaire Contact' },
                    { id: 'gallery', name: 'Grille Galerie' }
                ], data.name, null),
                parse: (el) => ({ name: el.querySelector('.data-comp-name').value })
            }
        };

        // État courant
        this.currentFilename = null;

        this.initEventListeners();
    }

    // --- Moteur de Rendu ---
    tplSelect(id, type, className, list, selected, placeholder) {
        const options = list.map(item =>
            `<option value="${item.id}" ${selected === item.id ? 'selected' : ''}>${item.name}</option>`
        ).join('');

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
                </div>
            </div>`;
    }

    // --- Gestion des Evénements ---
    initEventListeners() {
        const container = document.getElementById('page-blocks-container');

        // Suppression d'un bloc dans le builder
        container.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-delete-block')) {
                e.target.closest('.block-item').remove();
            }
        });

        // Ajout d'un bloc
        document.getElementById('btn-add-block').addEventListener('click', () => {
            const type = document.getElementById('select-block-type').value;
            this.addBlock(type);
        });

        // Sauvegarde
        document.getElementById('btn-save-page').addEventListener('click', () => this.savePage());

        // Nouveau layout vierge
        const btnNew = document.getElementById('btn-new-page');
        if (btnNew) {
            btnNew.addEventListener('click', () => this.resetEditor());
        }

        // Chargement d'une page existante (sidebar)
        const fileList = document.getElementById('file-list');
        if (fileList) {
            fileList.addEventListener('click', (e) => {
                // Chargement
                const link = e.target.closest('.load-page-link');
                if (link) {
                    e.preventDefault();
                    this.loadPageLayout(link.dataset.filename);
                    return;
                }
                // Suppression
                const btnDelete = e.target.closest('.btn-delete-file');
                if (btnDelete) {
                    e.preventDefault();
                    this.deletePage(btnDelete.dataset.filename, btnDelete.closest('li'));
                }
            });
        }

        // Mise à jour du nom de fichier en temps réel
        document.getElementById('page-title').addEventListener('input', (e) => {
            if (!this.currentFilename) {
                const slug = this.slugify(e.target.value);
                document.getElementById('generated-filename').textContent = slug ? slug + '.json' : 'nouveau.json';
            }
        });
    }

    // --- Actions ---
    async loadResources() {
        try {
            const res = await fetch('api/list_articles.php?meta=1');
            const articles = await res.json();
            this.resources.articles = Array.isArray(articles) ? articles : [];
        } catch (e) {
            console.error("Erreur chargement articles :", e);
        }
    }

    addBlock(type, data = {}) {
        if (!this.blockRegistry[type]) return;
        const id = 'block_' + Date.now();
        const html = this.blockRegistry[type].render(id, data);
        document.getElementById('page-blocks-container').insertAdjacentHTML('beforeend', html);
    }

    async loadPageLayout(filename) {
        try {
            const res = await fetch(`api/get_page.php?file=${encodeURIComponent(filename)}`);
            if (!res.ok) throw new Error("Réponse serveur invalide");

            const data = await res.json();
            if (data.success === false) throw new Error(data.error || "Erreur inconnue");

            // Mise à jour de l'interface
            this.currentFilename = filename;
            document.getElementById('page-title').value = data.meta?.id || filename.replace('.json', '');
            document.getElementById('generated-filename').textContent = filename;
            document.getElementById('page-blocks-container').innerHTML = '';

            if (Array.isArray(data.layout)) {
                data.layout.forEach(blockData => this.addBlock(blockData.type, blockData));
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
            if (this.blockRegistry[type]) {
                layout.push({ type, ...this.blockRegistry[type].parse(el) });
            }
        });

        // Payload compatible avec PageModel::save()
        const payload = {
            type: 'page',
            meta: {
                id: id,
                status: 'draft'
            },
            layout: layout
        };

        try {
            const res = await fetch('api/save_page.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();

            if (result.success) {
                this.currentFilename = result.filename;
                document.getElementById('generated-filename').textContent = result.filename;
                alert('Page enregistrée avec succès !');
                // Recharger la sidebar si on vient de créer une nouvelle page
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
            const res = await fetch('api/delete_page.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ filename })
            });
            const result = await res.json();

            if (result.success) {
                liElement?.remove();
                // Réinitialiser l'éditeur si c'était la page courante
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
        document.getElementById('page-title').value = '';
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
