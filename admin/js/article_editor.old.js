/**
 * Article Editor v2 - Compatible avec l'API Nucleus v2
 * 
 * Changements par rapport à v1 :
 * - Utilise le format 'data' unifié pour tous les blocs
 * - Appelle les endpoints API v2
 * - Affiche les erreurs de validation serveur
 */

// === CONFIGURATION ===
const configEl = document.getElementById('editor-langs');
const SUPPORTED_LANGS = JSON.parse(configEl.dataset.config);
const API_BASE = 'api/';

let activeLang = 'fr';
let originalCreationDate = null;
let currentFilename = null;

// === TEMPLATES DES BLOCS ===
const BlockTemplates = {
    title: (id, data = null) => createBlockWrapper(id, 'title', 'Titre (H2)', `
        <div class="field-group">
            <label>Niveau</label>
            <select class="block-level">
                ${[1, 2, 3, 4, 5, 6].map(n => `<option value="${n}" ${(data?.level || 2) == n ? 'selected' : ''}>H${n}</option>`).join('')}
            </select>
        </div>
        ${generateLangInputs(id, 'input', 'Titre', data)}
    `),

    text: (id, data = null) => createBlockWrapper(id, 'text', 'Paragraphe', `
        ${generateLangInputs(id, 'textarea', 'Contenu', data)}
    `),

    list: (id, data = null) => createBlockWrapper(id, 'list', 'Liste à puces', `
        <p class="field-hint">Séparez les éléments par une virgule</p>
        ${generateLangInputs(id, 'textarea', 'Élément 1, Élément 2, ...', data)}
    `),

    link: (id, data = null) => createBlockWrapper(id, 'link', 'Lien / Bouton', `
        <div class="field-group">
            <label>URL</label>
            <input type="url" class="block-url" placeholder="https://..." value="${data?.url || ''}">
        </div>
        <label class="field-label">Texte du lien</label>
        ${generateLangInputs(id, 'input', 'Texte du lien', data)}
    `),
    image: (id, data = null) => createBlockWrapper(id, 'image', 'Image', `
    <div class="field-group">
        <label>Chemin de l'image</label>
        <input type="text" class="block-src" placeholder="home/photo.jpg" value="${data?.src || ''}">
    </div>
    <div class="field-group">
        <label>Texte alternatif</label>
        <input type="text" class="block-alt" placeholder="Description de l'image" value="${escapeHtml(data?.alt || '')}">
    </div>
`)
};

// === FONCTIONS UTILITAIRES ===

/**
 * Génère les champs de saisie pour chaque langue
 */
function generateLangInputs(blockId, tag, placeholder, blockData = null) {
    return SUPPORTED_LANGS.map(lang => {
        let val = '';

        // Récupérer la valeur depuis 'data' (format v2)
        if (blockData?.data?.[lang] !== undefined) {
            val = blockData.data[lang];
            // Si c'est un tableau (liste), convertir en chaîne
            if (Array.isArray(val)) {
                val = val.join(', ');
            }
        }

        const inputPlaceholder = `${placeholder} (${lang.toUpperCase()})`;
        const isActive = lang === activeLang;

        const input = tag === 'input'
            ? `<input type="text" class="block-data" data-lang="${lang}" value="${escapeHtml(val)}" placeholder="${inputPlaceholder}">`
            : `<textarea class="block-data" data-lang="${lang}" placeholder="${inputPlaceholder}">${escapeHtml(val)}</textarea>`;

        return `
            <div class="lang-field" data-lang="${lang}" style="display: ${isActive ? 'block' : 'none'}">
                ${input}
            </div>
        `;
    }).join('');
}

/**
 * Crée l'enveloppe d'un bloc
 */
function createBlockWrapper(id, type, label, content) {
    const div = document.createElement('div');
    div.className = 'block-item';
    div.dataset.id = id;
    div.dataset.type = type;
    div.innerHTML = `
        <div class="block-header">
            <span class="block-type-label">${label}</span>
            <div class="block-actions">
                <button type="button" class="btn-move-up" title="Monter">↑</button>
                <button type="button" class="btn-move-down" title="Descendre">↓</button>
                <button type="button" class="btn-delete" title="Supprimer">×</button>
            </div>
        </div>
        <div class="block-body">${content}</div>
    `;

    // Événements des boutons
    div.querySelector('.btn-delete').addEventListener('click', () => div.remove());
    div.querySelector('.btn-move-up').addEventListener('click', () => moveBlock(div, -1));
    div.querySelector('.btn-move-down').addEventListener('click', () => moveBlock(div, 1));

    return div;
}

/**
 * Déplace un bloc vers le haut ou le bas
 */
function moveBlock(blockEl, direction) {
    const workspace = document.getElementById('blocks-workspace');
    const blocks = Array.from(workspace.children);
    const index = blocks.indexOf(blockEl);
    const newIndex = index + direction;

    if (newIndex < 0 || newIndex >= blocks.length) return;

    if (direction === -1) {
        workspace.insertBefore(blockEl, blocks[newIndex]);
    } else {
        workspace.insertBefore(blockEl, blocks[newIndex].nextSibling);
    }
}

/**
 * Échappe les caractères HTML
 */
function escapeHtml(str) {
    if (typeof str !== 'string') return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

/**
 * Ajoute un bloc au workspace
 */
function addBlock(type, data = null) {
    if (!BlockTemplates[type]) {
        console.error('Type de bloc inconnu:', type);
        return;
    }
    const id = data?.id || Date.now();
    const newBlock = BlockTemplates[type](id, data);
    document.getElementById('blocks-workspace').appendChild(newBlock);
}

// === CHANGEMENT DE LANGUE ===

function switchEditorLang(lang) {
    activeLang = lang;

    // Mise à jour des onglets
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.lang === lang);
    });

    // Affichage des champs correspondants
    document.querySelectorAll('.lang-field').forEach(field => {
        field.style.display = field.dataset.lang === lang ? 'block' : 'none';
    });
}

// Exposer globalement pour les onglets HTML
window.switchEditorLang = switchEditorLang;

// === COLLECTE DES DONNÉES ===

/**
 * Collecte les données du formulaire au format v2
 */
function collectArticleData() {
    const titleInput = document.getElementById('article-title');
    const articleId = titleInput?.value.trim() || '';

    if (!articleId) {
        return { error: "L'article doit avoir un ID (champ titre)" };
    }

    const articleData = {
        type: 'article',
        meta: {
            id: document.getElementById('generated-id').textContent || articleId,
            created: originalCreationDate || new Date().toISOString().split('T')[0],
            updated: new Date().toISOString().split('T')[0],
            status: 'draft'
        },
        content: []
    };

    const blocks = document.querySelectorAll('.block-item');

    blocks.forEach(block => {
        const type = block.dataset.type;
        const blockObj = { type };

        // Champs spécifiques selon le type
        if (type === 'title') {
            const levelSelect = block.querySelector('.block-level');
            blockObj.level = parseInt(levelSelect?.value || 2);
        }

        if (type === 'link') {
            const urlInput = block.querySelector('.block-url');
            blockObj.url = urlInput?.value || '#';
        }
        if (type === 'image') {
            blockObj.src = block.querySelector('.block-src')?.value || '';
            blockObj.alt = block.querySelector('.block-alt')?.value || '';
        }

        // Collecte des données multilingues (format unifié 'data')
        blockObj.data = {};

        SUPPORTED_LANGS.forEach(lang => {
            const field = block.querySelector(`.block-data[data-lang="${lang}"]`);
            if (field) {
                let value = field.value;

                // Convertir en tableau pour les listes
                if (type === 'list') {
                    blockObj.data[lang] = value
                        .split(',')
                        .map(item => item.trim())
                        .filter(item => item !== '');
                } else {
                    blockObj.data[lang] = value;
                }
            }
        });

        articleData.content.push(blockObj);
    });

    return articleData;
}

// === OPÉRATIONS API ===

/**
 * Charge un article depuis l'API v2
 */
async function loadArticle(filename) {
    try {
        const response = await fetch(`${API_BASE}get_article.php?file=${encodeURIComponent(filename)}`);

        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const data = await response.json();

        if (data.success === false) {
            throw new Error(data.error || 'Erreur inconnue');
        }

        // Stocker les infos pour la sauvegarde
        originalCreationDate = data.meta?.created || null;
        currentFilename = filename;

        // Réinitialiser l'éditeur
        const workspace = document.getElementById('blocks-workspace');
        workspace.innerHTML = '';

        // Remplir le titre
        const titleInput = document.getElementById('article-title');
        if (titleInput) {
            titleInput.value = data.meta?.id || '';
            document.getElementById('generated-id').textContent = data.meta?.id || '--';
        }

        // Créer les blocs
        if (data.content && Array.isArray(data.content)) {
            data.content.forEach(block => addBlock(block.type, block));
        }

        showNotification('Article chargé', 'success');

    } catch (error) {
        console.error('Erreur chargement:', error);
        showNotification(`Erreur: ${error.message}`, 'error');
    }
}

/**
 * Sauvegarde l'article via l'API v2
 */
async function saveArticle() {
    const articleData = collectArticleData();

    if (articleData.error) {
        showNotification(articleData.error, 'error');
        return;
    }

    try {
        const response = await fetch(`${API_BASE}save_article.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(articleData)
        });

        const result = await response.json();

        if (result.success) {
            currentFilename = result.filename;
            showNotification('Article enregistré !', 'success');

            // Recharger la sidebar
            setTimeout(() => location.reload(), 1000);
        } else {
            // Afficher les erreurs de validation
            const errorMsg = result.errors?.join('\n') || 'Erreur inconnue';
            showNotification(errorMsg, 'error');
        }

    } catch (error) {
        console.error('Erreur sauvegarde:', error);
        showNotification(`Erreur: ${error.message}`, 'error');
    }
}

/**
 * Supprime un article via l'API v2
 */
async function deleteArticle(filename) {
    if (!confirm(`Supprimer définitivement "${filename}" ?`)) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE}delete_article.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ filename })
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Article supprimé', 'success');
            setTimeout(() => location.reload(), 500);
        } else {
            throw new Error(result.errors?.join(', ') || 'Erreur de suppression');
        }

    } catch (error) {
        console.error('Erreur suppression:', error);
        showNotification(`Erreur: ${error.message}`, 'error');
    }
}

/**
 * Réinitialise l'éditeur pour un nouvel article
 */
function resetEditor() {
    if (!confirm('Créer un nouvel article ? Les modifications non sauvegardées seront perdues.')) {
        return;
    }

    originalCreationDate = null;
    currentFilename = null;

    document.getElementById('article-title').value = '';
    document.getElementById('generated-id').textContent = '--';
    document.getElementById('blocks-workspace').innerHTML = '';

    window.scrollTo(0, 0);
    showNotification('Nouvel article', 'info');
}

// === NOTIFICATIONS ===

function showNotification(message, type = 'info') {
    // Supprimer les notifications existantes
    document.querySelectorAll('.notification').forEach(n => n.remove());

    const div = document.createElement('div');
    div.className = `notification notification-${type}`;
    div.textContent = message;
    div.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 8px;
        color: white;
        font-weight: bold;
        z-index: 9999;
        animation: slideIn 0.3s ease;
        max-width: 400px;
        white-space: pre-line;
    `;

    const colors = {
        success: '#10b981',
        error: '#ef4444',
        info: '#3b82f6'
    };
    div.style.background = colors[type] || colors.info;

    document.body.appendChild(div);

    setTimeout(() => div.remove(), 5000);
}

// === GÉNÉRATION DE L'ID ===

function generateSlug(text) {
    return text
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

// === INITIALISATION ===

document.addEventListener('DOMContentLoaded', () => {
    // Génération automatique de l'ID
    const titleInput = document.getElementById('article-title');
    if (titleInput) {
        titleInput.addEventListener('input', (e) => {
            const slug = generateSlug(e.target.value);
            document.getElementById('generated-id').textContent = slug || '--';
        });
    }

    // Ajout de bloc
    const addBlockBtn = document.getElementById('add-block-trigger');
    if (addBlockBtn) {
        addBlockBtn.addEventListener('click', () => {
            const type = document.getElementById('new-block-type').value;
            addBlock(type);
        });
    }

    // Sauvegarde
    const saveBtn = document.getElementById('save-article-btn');
    if (saveBtn) {
        saveBtn.addEventListener('click', saveArticle);
    }

    // Nouvel article
    const newBtn = document.getElementById('new-article-btn');
    if (newBtn) {
        newBtn.addEventListener('click', resetEditor);
    }

    // Chargement d'un article existant
    document.querySelectorAll('.load-article-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const filename = link.dataset.filename;
            if (confirm('Charger cet article ? Les modifications non sauvegardées seront perdues.')) {
                loadArticle(filename);
            }
        });
    });

    // Suppression d'un article
    document.querySelectorAll('.btn-delete-file').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const filename = btn.dataset.filename;
            deleteArticle(filename);
        });
    });
});