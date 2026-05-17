/**
 * PageEditor - Architecture Data-Driven
 */
class PageEditor {
    constructor() {
        // 1. Le "Store" (Data Context)
        this.resources = {
            articles: [],
            contacts: [],
            galleries: []
        };

        // 2. Le Registre des Blocs (Registry Pattern)
        this.blockRegistry = {
            article_ref: {
                title: "📄 Section : Article",
                render: (id, data) => this.tplSelect(id, 'article_ref', 'data-filename', this.resources.articles, data.filename, "-- Sélectionner un article --"),
                parse: (el) => ({ filename: el.querySelector('.data-filename').value })
            },
            gallery_ref: {
                title: "🖼️ Galerie Photo",
                render: (id, data) => this.tplSelect(id, 'gallery_ref', 'data-folder', this.resources.galleries, data.folder, "-- Choisir une galerie --", true),
                parse: (el) => ({ folder: el.querySelector('.data-folder').value })
            },
            ui_component: {
                title: "⚙️ Composant UI",
                render: (id, data) => {
                    const options = [
                        { id: 'hero', name: 'Bannière Hero' },
                        { id: 'contact', name: 'Formulaire Contact' },
                        { id: 'gallery', name: 'Grille Galerie' }
                    ];
                    return this.tplSelect(id, 'ui_component', 'data-comp-name', options, data.name, null);
                },
                parse: (el) => ({ name: el.querySelector('.data-comp-name').value })
            },
            contact_ref: {
                title: "👤 Référence Contact",
                render: (id, data) => this.tplSelect(id, 'contact_ref', 'data-filename', this.resources.contacts, data.filename, "-- Sélectionner un contact --"),
                parse: (el) => ({ filename: el.querySelector('.data-filename').value })
            }
        };

        this.initEventListeners();
    }

    // --- Moteur de Rendu ---
    tplSelect(id, type, className, list, selected, placeholder, isGallery = false) {
        const options = list.map(item => {
            const val = isGallery ? item.id : item; // Adaptation pour l'objet gallery
            const label = isGallery ? item.name : item;
            return `<option value="${val}" ${selected === val ? 'selected' : ''}>${label}</option>`;
        }).join('');

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

    // --- Gestion des Evénements (Délégation) ---
    initEventListeners() {
        const container = document.getElementById('page-blocks-container');

        // 1. Gérer les suppressions de blocs (Délégation)
        container.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-delete-block')) {
                e.target.closest('.block-item').remove();
            }
        });

        // 2. Gérer l'ajout de nouveaux blocs
        document.getElementById('btn-add-block').addEventListener('click', () => {
            const type = document.getElementById('select-block-type').value;
            this.addBlock(type);
        });

        // 3. Gérer la sauvegarde
        document.getElementById('btn-save-page').addEventListener('click', () => this.savePage());

        // 4. Charger une page existante (Ton nouvel ajout)
        const fileList = document.getElementById('file-list');
        if (fileList) {
            fileList.addEventListener('click', (e) => {
                const link = e.target.closest('.load-page-link');
                if (link) {
                    e.preventDefault();
                    this.loadPageLayout(link.dataset.filename);
                }
            });
        }
    }

    // --- Actions ---
    async loadResources() {
        const galleryPath = '../public/img/content/galleries/galleries_index.json';
        try {
            const [art, con, gal] = await Promise.all([
                fetch('api/get_articles_list.php').then(r => r.json()),
                fetch('api/get_contacts_list.php').then(r => r.json()),
                fetch(`${galleryPath}?t=${Date.now()}`).then(r => r.ok ? r.json() : [])
            ]);
            this.resources = { articles: art, contacts: con, galleries: gal };
            document.getElementById('btn-add-block').disabled = false;
        } catch (e) { console.error("Erreur resources:", e); }
    }

    addBlock(type, data = {}) {
        if (!this.blockRegistry[type]) return;
        const id = 'block_' + Date.now();
        const html = this.blockRegistry[type].render(id, data);
        document.getElementById('page-blocks-container').insertAdjacentHTML('beforeend', html);
    }
    async loadPageLayout(filename) {
        console.log("Chargement de la page :", filename);
        const container = document.getElementById('page-blocks-container');

        try {
            const response = await fetch(`../json/pages/${filename}?t=${Date.now()}`);
            if (!response.ok) throw new Error("Fichier introuvable");

            const data = await response.json();

            // Mise à jour de l'interface
            document.getElementById('page-title').value = data.title || '';
            document.getElementById('generated-filename').textContent = filename;
            container.innerHTML = ''; // On vide le container avant de reconstruire

            if (data.layout && Array.isArray(data.layout)) {
                data.layout.forEach(blockData => {
                    // On utilise la méthode de la classe pour rester cohérent
                    this.addBlock(blockData.type, blockData);
                });
                console.log("Layout reconstruit avec succès.");
            }
        } catch (e) {
            console.error("Erreur de chargement :", e);
            alert("Erreur lors du chargement du layout.");
        }
    }

    savePage() {
        const blocks = [];
        document.querySelectorAll('.block-item').forEach(el => {
            const type = el.dataset.type;
            if (this.blockRegistry[type]) {
                const blockData = { type, ...this.blockRegistry[type].parse(el) };
                blocks.push(blockData);
            }
        });

        const title = document.getElementById('page-title').value;
        const payload = {
            title: title,
            filename: title.toLowerCase().replace(/\s+/g, '-') + '.json',
            layout: blocks
        };

        fetch('api/save_page.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        }).then(res => res.json()).then(res => {
            if (res.success) alert('Enregistré avec succès !');
        });
    }
}

// Initialisation
const editor = new PageEditor();
editor.loadResources();