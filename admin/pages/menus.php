<?php
// Fragment admin — chargé par inc/main.php
// Éditeur de menus

require_once ROOT_PATH . 'src/utils/json_handler.php';

$menusPath = DIR_JSON . 'menus.json';
$menus = JsonHandler::load($menusPath);
$mainMenu = $menus['Main_menu'] ?? [];
$rsMenu = $menus['RS_menu'] ?? [];
$langs = ConfigModel::getLangs();
$langCodes = array_column($langs, 'code');
?>

<div class="admin-editor-container">

    <aside class="admin-sidebar">
        <h4>Navigation</h4>
        <ul>
            <li class="sidebar-item">
                <a href="#section-main" class="item-main">Menu principal</a>
            </li>
            <li class="sidebar-item">
                <a href="#section-rs" class="item-main">Réseaux sociaux</a>
            </li>
        </ul>
    </aside>

    <main class="admin-content">

        <!-- ===== MENU PRINCIPAL ===== -->
        <section class="form-section" id="section-main">
            <h4>Menu principal</h4>

            <div id="main-menu-list">
                <?php foreach ($mainMenu as $i => $entry): ?>
                    <div class="menu-entry">
                        <div class="menu-entry__header">
                            <div class="menu-entry__controls">
                                <button type="button" class="btn-move-up" title="Monter">↑</button>
                                <button type="button" class="btn-move-down" title="Descendre">↓</button>
                            </div>
                            <input type="text" class="entry-page main-title-input" placeholder="identifiant (ex: home)"
                                value="<?= htmlspecialchars($entry['page']) ?>">
                            <button type="button" class="btn-delete-entry btn-delete-file" title="Supprimer">🗑️</button>
                        </div>
                        <div class="menu-entry__langs">
                            <?php foreach ($langs as $langue): ?>
                                <div class="lang-row">
                                    <label><?= htmlspecialchars($langue['label']) ?></label>
                                    <input type="text" class="entry-titre" data-lang="<?= htmlspecialchars($langue['code']) ?>"
                                        placeholder="Titre <?= htmlspecialchars($langue['code']) ?>"
                                        value="<?= htmlspecialchars($entry['titre'][$langue['code']] ?? '') ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" id="btn-add-entry" class="btn-secondary">+ Ajouter une entrée</button>
        </section>

        <!-- ===== RS MENU ===== -->
        <section class="form-section" id="section-rs">
            <h4>Réseaux sociaux</h4>

            <div id="rs-menu-list">
                <?php foreach ($rsMenu as $i => $entry): ?>
                    <div class="menu-entry">
                        <div class="menu-entry__header">
                            <div class="menu-entry__controls">
                                <button type="button" class="btn-move-up" title="Monter">↑</button>
                                <button type="button" class="btn-move-down" title="Descendre">↓</button>
                            </div>
                            <input type="text" class="entry-page main-title-input" placeholder="URL (https://...)"
                                value="<?= htmlspecialchars($entry['page']) ?>">
                            <input type="text" class="entry-rs-titre main-title-input"
                                placeholder="Nom (facebook, instagram...)" value="<?= htmlspecialchars($entry['titre']) ?>">
                            <button type="button" class="btn-delete-entry btn-delete-file" title="Supprimer">🗑️</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" id="btn-add-rs" class="btn-secondary">+ Ajouter un réseau</button>
        </section>

        <!-- ===== ACTIONS ===== -->
        <div class="editor-actions-bar">
            <button type="button" id="btn-save-menus" class="btn-save">
                Enregistrer
            </button>
            <p id="save-status" class="field-hint"></p>
        </div>

    </main>
</div>

<script>
    const API = 'api/';
    const LANG_CODES = <?= json_encode($langCodes) ?>;
    const LANG_LABELS = <?= json_encode(array_column($langs, 'label', 'code')) ?>;

    // === TEMPLATE ENTRÉE PRINCIPALE ===
    function newMainEntry() {
        const langFields = LANG_CODES.map(lang => `
        <div class="lang-row">
            <label>${LANG_LABELS[lang] || lang}</label>
            <input type="text" class="entry-titre" data-lang="${lang}" placeholder="Titre ${lang}">
        </div>
    `).join('');

        const div = document.createElement('div');
        div.className = 'menu-entry';
        div.innerHTML = `
        <div class="menu-entry__header">
            <div class="menu-entry__controls">
                <button type="button" class="btn-move-up" title="Monter">↑</button>
                <button type="button" class="btn-move-down" title="Descendre">↓</button>
            </div>
            <input type="text" class="entry-page main-title-input" placeholder="identifiant (ex: home)">
            <button type="button" class="btn-delete-entry btn-delete-file" title="Supprimer">🗑️</button>
        </div>
        <div class="menu-entry__langs">${langFields}</div>
    `;
        return div;
    }

    // === TEMPLATE ENTRÉE RS ===
    function newRsEntry() {
        const div = document.createElement('div');
        div.className = 'menu-entry';
        div.innerHTML = `
        <div class="menu-entry__header">
            <div class="menu-entry__controls">
                <button type="button" class="btn-move-up" title="Monter">↑</button>
                <button type="button" class="btn-move-down" title="Descendre">↓</button>
            </div>
            <input type="text" class="entry-page main-title-input" placeholder="URL (https://...)">
            <input type="text" class="entry-rs-titre main-title-input" placeholder="Nom (facebook...)">
            <button type="button" class="btn-delete-entry btn-delete-file" title="Supprimer">🗑️</button>
        </div>
    `;
        return div;
    }

    // === DÉPLACEMENT ===
    function moveEntry(el, direction) {
        const list = el.closest('[id$="-menu-list"]');
        const entries = Array.from(list.querySelectorAll('.menu-entry'));
        const index = entries.indexOf(el);
        const newIndex = index + direction;

        if (newIndex < 0 || newIndex >= entries.length) return;

        direction === -1
            ? list.insertBefore(el, entries[newIndex])
            : list.insertBefore(el, entries[newIndex].nextSibling);
    }

    // === DÉLÉGATION ÉVÉNEMENTS ===
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('btn-delete-entry')) {
            e.target.closest('.menu-entry').remove();
        }
        if (e.target.classList.contains('btn-move-up')) {
            moveEntry(e.target.closest('.menu-entry'), -1);
        }
        if (e.target.classList.contains('btn-move-down')) {
            moveEntry(e.target.closest('.menu-entry'), 1);
        }
    });

    document.getElementById('btn-add-entry').addEventListener('click', () => {
        document.getElementById('main-menu-list').appendChild(newMainEntry());
    });

    document.getElementById('btn-add-rs').addEventListener('click', () => {
        document.getElementById('rs-menu-list').appendChild(newRsEntry());
    });

    // === COLLECTE ===
    function collectMenus() {
        const mainMenu = [];
        const rsMenu = [];

        document.querySelectorAll('#main-menu-list .menu-entry').forEach(entry => {
            const page = entry.querySelector('.entry-page')?.value.trim() || '';
            const titre = {};
            entry.querySelectorAll('.entry-titre').forEach(input => {
                titre[input.dataset.lang] = input.value.trim();
            });
            mainMenu.push({ page, titre });
        });

        document.querySelectorAll('#rs-menu-list .menu-entry').forEach(entry => {
            const page = entry.querySelector('.entry-page')?.value.trim() || '';
            const titre = entry.querySelector('.entry-rs-titre')?.value.trim() || '';
            rsMenu.push({ page, titre });
        });

        return { Main_menu: mainMenu, RS_menu: rsMenu };
    }

    // === SAUVEGARDE ===
    document.getElementById('btn-save-menus').addEventListener('click', async () => {
        const status = document.getElementById('save-status');
        status.textContent = 'Enregistrement...';

        try {
            const res = await fetch(API + 'save_menus.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(collectMenus())
            });
            const result = await res.json();

            if (result.success) {
                let msg = 'Enregistré ✓';
                if (result.created?.length) {
                    msg += ` — Pages créées : ${result.created.join(', ')}`;
                }
                status.textContent = msg;
                setTimeout(() => status.textContent = '', 5000);
            } else {
                const msg = result.errors?.join('\n') || result.error || 'Erreur inconnue';
                status.textContent = 'Erreur : ' + msg;
            }
        } catch (e) {
            status.textContent = 'Erreur : ' + e.message;
        }
    });
</script>