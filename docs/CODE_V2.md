# Nucleus CMS - Code v2
# Date : 2025-01-15
# Session : Création du socle backend + API articles

---

## STRUCTURE DES NOUVEAUX FICHIERS
/src/
├── utils/
│ └── json_handler.php
└── core/
├── block_registry.php
└── component_model.php

/admin/
├── api/
│ └── v2/
│ ├── save_article.php
│ ├── get_article.php
│ ├── delete_article.php
│ └── list_articles.php
├── js/
│ └── article_editor.js (remplace l'ancien)
└── src/
└── model/
└── config_model.php (à créer/remplacer)

/json/
└── articles/
└── test-nucleus.json (fichier de test)


---

## /src/utils/json_handler.php

```php
<?php
/**
 * JsonHandler - Gestionnaire centralisé des fichiers JSON
 */
class JsonHandler
{
    public static function load(string $path): array
    {
        if (!file_exists($path)) {
            throw new Exception("Fichier introuvable : {$path}");
        }

        $content = file_get_contents($path);
        
        if ($content === false) {
            throw new Exception("Impossible de lire : {$path}");
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON invalide dans {$path} : " . json_last_error_msg());
        }

        return $data;
    }

    public static function save(string $path, array $data): bool
    {
        $dir = dirname($path);
        
        if (!is_dir($dir)) {
            throw new Exception("Répertoire inexistant : {$dir}");
        }

        if (!is_writable($dir)) {
            throw new Exception("Répertoire non accessible en écriture : {$dir}");
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new Exception("Impossible d'encoder les données en JSON");
        }

        $tempPath = $path . '.tmp';
        
        if (file_put_contents($tempPath, $json) === false) {
            throw new Exception("Impossible d'écrire le fichier temporaire");
        }

        if (!rename($tempPath, $path)) {
            unlink($tempPath);
            throw new Exception("Impossible de finaliser l'écriture");
        }

        return true;
    }

    public static function delete(string $path): bool
    {
        if (!file_exists($path)) {
            throw new Exception("Fichier inexistant : {$path}");
        }

        if (!unlink($path)) {
            throw new Exception("Impossible de supprimer : {$path}");
        }

        return true;
    }

    public static function listFiles(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $files = scandir($dir);
        
        return array_values(array_filter($files, function($file) use ($dir) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'json' 
                   && is_file($dir . DIRECTORY_SEPARATOR . $file);
        }));
    }

    public static function exists(string $path): bool
    {
        return file_exists($path) && is_file($path);
    }
}
```

## /src/utils/block_registry.php

```php
<?php
/**
 * BlockRegistry - Définition et validation des types de blocs
 * 
 * Chaque type de bloc déclare :
 * - label : Nom affiché dans l'admin
 * - fields : Champs spécifiques au type (hors 'data' multilingue)
 * - dataType : Type de la donnée multilingue ('string', 'array')
 */
class BlockRegistry
{
    private static array $types = [
        'title' => [
            'label' => 'Titre',
            'fields' => [
                'level' => [
                    'type' => 'int',
                    'default' => 2,
                    'min' => 1,
                    'max' => 6
                ]
            ],
            'dataType' => 'string'
        ],
        'text' => [
            'label' => 'Paragraphe',
            'fields' => [],
            'dataType' => 'string'
        ],
        'list' => [
            'label' => 'Liste à puces',
            'fields' => [],
            'dataType' => 'array'
        ],
        'link' => [
            'label' => 'Lien / Bouton',
            'fields' => [
                'url' => [
                    'type' => 'url',
                    'required' => true
                ]
            ],
            'dataType' => 'string'
        ]
    ];

    public static function exists(string $type): bool
    {
        return isset(self::$types[$type]);
    }

    public static function get(string $type): ?array
    {
        return self::$types[$type] ?? null;
    }

    public static function all(): array
    {
        return self::$types;
    }

    public static function getOptions(): array
    {
        $options = [];
        foreach (self::$types as $key => $def) {
            $options[$key] = $def['label'];
        }
        return $options;
    }

    public static function validate(array $block, array $langs): array
    {
        $errors = [];

        if (!isset($block['type'])) {
            return ['valid' => false, 'errors' => ['Type de bloc manquant']];
        }

        if (!self::exists($block['type'])) {
            return ['valid' => false, 'errors' => ["Type inconnu : {$block['type']}"]];
        }

        $def = self::$types[$block['type']];

        foreach ($def['fields'] as $fieldName => $fieldDef) {
            $value = $block[$fieldName] ?? null;

            if (($fieldDef['required'] ?? false) && empty($value)) {
                $errors[] = "Champ '{$fieldName}' requis pour le type '{$block['type']}'";
                continue;
            }

            if ($value !== null) {
                switch ($fieldDef['type']) {
                    case 'int':
                        if (!is_numeric($value)) {
                            $errors[] = "'{$fieldName}' doit être un nombre";
                        } else {
                            $intVal = (int) $value;
                            if (isset($fieldDef['min']) && $intVal < $fieldDef['min']) {
                                $errors[] = "'{$fieldName}' minimum : {$fieldDef['min']}";
                            }
                            if (isset($fieldDef['max']) && $intVal > $fieldDef['max']) {
                                $errors[] = "'{$fieldName}' maximum : {$fieldDef['max']}";
                            }
                        }
                        break;

                    case 'url':
                        if (!filter_var($value, FILTER_VALIDATE_URL) && $value !== '#') {
                            $errors[] = "'{$fieldName}' doit être une URL valide";
                        }
                        break;
                }
            }
        }

        if (!isset($block['data']) || !is_array($block['data'])) {
            $errors[] = "Champ 'data' manquant ou invalide";
        } else {
            foreach ($langs as $lang) {
                if (!array_key_exists($lang, $block['data'])) {
                    $errors[] = "Langue '{$lang}' manquante dans 'data'";
                } else {
                    $langValue = $block['data'][$lang];
                    
                    if ($def['dataType'] === 'array' && !is_array($langValue)) {
                        $errors[] = "'{$lang}' doit être un tableau pour le type '{$block['type']}'";
                    }
                    if ($def['dataType'] === 'string' && !is_string($langValue)) {
                        $errors[] = "'{$lang}' doit être une chaîne pour le type '{$block['type']}'";
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    public static function normalize(array $block, array $langs): array
    {
        if (!self::exists($block['type'])) {
            return $block;
        }

        $def = self::$types[$block['type']];

        foreach ($def['fields'] as $fieldName => $fieldDef) {
            if (!isset($block[$fieldName]) && isset($fieldDef['default'])) {
                $block[$fieldName] = $fieldDef['default'];
            }
        }

        if (!isset($block['data'])) {
            $block['data'] = [];
        }

        $emptyValue = $def['dataType'] === 'array' ? [] : '';
        
        foreach ($langs as $lang) {
            if (!isset($block['data'][$lang])) {
                $block['data'][$lang] = $emptyValue;
            }
        }

        return $block;
    }
}
```
## /src/core/component_model.php

```php
<?php
/**
 * ComponentModel - CRUD générique pour les composants Nucleus
 */

require_once __DIR__ . '/../utils/json_handler.php';
require_once __DIR__ . '/block_registry.php';

class ComponentModel
{
    private string $storageDir;
    private array $langs;
    private string $componentType;

    public function __construct(string $storageDir, array $langs, string $componentType = 'article')
    {
        $this->storageDir = rtrim($storageDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->langs = $langs;
        $this->componentType = $componentType;
    }

    public function generateId(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[àáâãäå]/u', 'a', $slug);
        $slug = preg_replace('/[èéêë]/u', 'e', $slug);
        $slug = preg_replace('/[ìíîï]/u', 'i', $slug);
        $slug = preg_replace('/[òóôõö]/u', 'o', $slug);
        $slug = preg_replace('/[ùúûü]/u', 'u', $slug);
        $slug = preg_replace('/[ç]/u', 'c', $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug ?: 'composant-' . time();
    }

    public function createEmpty(string $title): array
    {
        $id = $this->generateId($title);
        
        return [
            'type' => $this->componentType,
            'meta' => [
                'id' => $id,
                'created' => date('Y-m-d'),
                'updated' => date('Y-m-d'),
                'status' => 'draft',
                'author' => $_SESSION['user'] ?? 'admin'
            ],
            'content' => []
        ];
    }

    public function load(string $identifier): array
    {
        $filename = $this->resolveFilename($identifier);
        $path = $this->storageDir . $filename;
        
        return JsonHandler::load($path);
    }

    public function save(array $data): array
    {
        $errors = [];

        if (!isset($data['type'])) {
            $errors[] = "Type de composant manquant";
        }

        if (!isset($data['meta']['id']) || empty($data['meta']['id'])) {
            $errors[] = "ID du composant manquant";
        }

        if (!isset($data['content']) || !is_array($data['content'])) {
            $errors[] = "Contenu manquant ou invalide";
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'filename' => null];
        }

        $normalizedContent = [];
        
        foreach ($data['content'] as $index => $block) {
            $block = BlockRegistry::normalize($block, $this->langs);
            $validation = BlockRegistry::validate($block, $this->langs);
            
            if (!$validation['valid']) {
                foreach ($validation['errors'] as $err) {
                    $errors[] = "Bloc #" . ($index + 1) . " : {$err}";
                }
            }
            
            $normalizedContent[] = $block;
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'filename' => null];
        }

        $data['content'] = $normalizedContent;
        $data['meta']['updated'] = date('Y-m-d');

        $filename = $data['meta']['id'] . '.json';
        $path = $this->storageDir . $filename;

        try {
            JsonHandler::save($path, $data);
            return ['success' => true, 'errors' => [], 'filename' => $filename];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()], 'filename' => null];
        }
    }

    public function delete(string $identifier): array
    {
        $filename = $this->resolveFilename($identifier);
        $path = $this->storageDir . $filename;

        try {
            JsonHandler::delete($path);
            return ['success' => true, 'errors' => []];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }

    public function listAll(bool $withMeta = false): array
    {
        $files = JsonHandler::listFiles($this->storageDir);
        
        if (!$withMeta) {
            return $files;
        }

        $components = [];
        
        foreach ($files as $filename) {
            try {
                $data = JsonHandler::load($this->storageDir . $filename);
                $components[] = [
                    'filename' => $filename,
                    'id' => $data['meta']['id'] ?? $filename,
                    'status' => $data['meta']['status'] ?? 'unknown',
                    'updated' => $data['meta']['updated'] ?? null,
                    'blocksCount' => count($data['content'] ?? [])
                ];
            } catch (Exception $e) {
                $components[] = [
                    'filename' => $filename,
                    'id' => $filename,
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $components;
    }

    public function exists(string $identifier): bool
    {
        $filename = $this->resolveFilename($identifier);
        return JsonHandler::exists($this->storageDir . $filename);
    }

    private function resolveFilename(string $identifier): string
    {
        if (substr($identifier, -5) === '.json') {
            return $identifier;
        }
        return $identifier . '.json';
    }
}
```
## /admin/api/v2/save_article.php
```php
<?php
session_start();

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../../config_admin.php';
require_once ROOT_PATH . 'src/core/component_model.php';

header('Content-Type: application/json; charset=utf-8');

$jsonInput = file_get_contents('php://input');
$data = json_decode($jsonInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'JSON invalide']);
    exit;
}

$langs = ['fr', 'en'];
$model = new ComponentModel(JSON_ARTICLES_DIR, $langs, 'article');

$result = $model->save($data);

echo json_encode($result);
```
## /admin/api/v2/get_article.php
```php
<?php
session_start();

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../../config_admin.php';
require_once ROOT_PATH . 'src/core/component_model.php';

header('Content-Type: application/json; charset=utf-8');

$file = $_GET['file'] ?? null;

if (!$file) {
    echo json_encode(['success' => false, 'error' => 'Paramètre file manquant']);
    exit;
}

$langs = ['fr', 'en'];
$model = new ComponentModel(JSON_ARTICLES_DIR, $langs, 'article');

try {
    $data = $model->load($file);
    echo json_encode($data);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
```

## /admin/api/v2/delete_article.php
```php
<?php
session_start();

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../../config_admin.php';
require_once ROOT_PATH . 'src/core/component_model.php';

header('Content-Type: application/json; charset=utf-8');

$jsonInput = file_get_contents('php://input');
$data = json_decode($jsonInput, true);

$filename = $data['filename'] ?? null;

if (!$filename) {
    echo json_encode(['success' => false, 'error' => 'Nom de fichier manquant']);
    exit;
}

$langs = ['fr', 'en'];
$model = new ComponentModel(JSON_ARTICLES_DIR, $langs, 'article');

$result = $model->delete($filename);

echo json_encode($result);
```

## /admin/api/v2/list_articles.php
```php
<?php
session_start();

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../../config_admin.php';
require_once ROOT_PATH . 'src/core/component_model.php';

header('Content-Type: application/json; charset=utf-8');

$withMeta = isset($_GET['meta']) && $_GET['meta'] === '1';

$langs = ['fr', 'en'];
$model = new ComponentModel(JSON_ARTICLES_DIR, $langs, 'article');

$list = $model->listAll($withMeta);

echo json_encode($list);
```
## /admin/src/model/config_model.php
```php
<?php
/**
 * ConfigModel Admin - Accès statique à la configuration
 */
class ConfigModel
{
    private static ?array $langs = null;

    public static function getLangs(): array
    {
        if (self::$langs === null) {
            self::loadConfig();
        }
        return self::$langs;
    }

    private static function loadConfig(): void
    {
        $configPath = ROOT_PATH . 'json/config.json';
        
        if (!file_exists($configPath)) {
            self::$langs = ['fr' => 'Français', 'en' => 'English'];
            return;
        }

        $data = json_decode(file_get_contents($configPath), true);
        
        self::$langs = [];
        
        if (isset($data['langs']) && is_array($data['langs'])) {
            foreach ($data['langs'] as $langItem) {
                foreach ($langItem as $code => $label) {
                    self::$langs[$code] = $label;
                }
            }
        }

        if (empty(self::$langs)) {
            self::$langs = ['fr' => 'Français', 'en' => 'English'];
        }
    }
}
```
## /admin/js/article_editor.js
```javascript
/**
 * Article Editor v2 - Compatible avec l'API Nucleus v2
 */

const configEl = document.getElementById('editor-langs');
const SUPPORTED_LANGS = JSON.parse(configEl.dataset.config);
const API_BASE = 'api/v2/';

let activeLang = 'fr';
let originalCreationDate = null;
let currentFilename = null;

const BlockTemplates = {
    title: (id, data = null) => createBlockWrapper(id, 'title', 'Titre (H2)', `
        <div class="field-group">
            <label>Niveau</label>
            <select class="block-level">
                ${[1,2,3,4,5,6].map(n => `<option value="${n}" ${(data?.level || 2) == n ? 'selected' : ''}>H${n}</option>`).join('')}
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
    `)
};

function generateLangInputs(blockId, tag, placeholder, blockData = null) {
    return SUPPORTED_LANGS.map(lang => {
        let val = '';
        
        if (blockData?.data?.[lang] !== undefined) {
            val = blockData.data[lang];
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

    div.querySelector('.btn-delete').addEventListener('click', () => div.remove());
    div.querySelector('.btn-move-up').addEventListener('click', () => moveBlock(div, -1));
    div.querySelector('.btn-move-down').addEventListener('click', () => moveBlock(div, 1));

    return div;
}

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

function escapeHtml(str) {
    if (typeof str !== 'string') return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function addBlock(type, data = null) {
    if (!BlockTemplates[type]) {
        console.error('Type de bloc inconnu:', type);
        return;
    }
    const id = data?.id || Date.now();
    const newBlock = BlockTemplates[type](id, data);
    document.getElementById('blocks-workspace').appendChild(newBlock);
}

function switchEditorLang(lang) {
    activeLang = lang;
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.lang === lang);
    });
    
    document.querySelectorAll('.lang-field').forEach(field => {
        field.style.display = field.dataset.lang === lang ? 'block' : 'none';
    });
}

window.switchEditorLang = switchEditorLang;

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

        if (type === 'title') {
            const levelSelect = block.querySelector('.block-level');
            blockObj.level = parseInt(levelSelect?.value || 2);
        }

        if (type === 'link') {
            const urlInput = block.querySelector('.block-url');
            blockObj.url = urlInput?.value || '#';
        }

        blockObj.data = {};
        
        SUPPORTED_LANGS.forEach(lang => {
            const field = block.querySelector(`.block-data[data-lang="${lang}"]`);
            if (field) {
                let value = field.value;
                
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

        originalCreationDate = data.meta?.created || null;
        currentFilename = filename;

        const workspace = document.getElementById('blocks-workspace');
        workspace.innerHTML = '';

        const titleInput = document.getElementById('article-title');
        if (titleInput) {
            titleInput.value = data.meta?.id || '';
            document.getElementById('generated-id').textContent = data.meta?.id || '--';
        }

        if (data.content && Array.isArray(data.content)) {
            data.content.forEach(block => addBlock(block.type, block));
        }

        showNotification('Article chargé', 'success');

    } catch (error) {
        console.error('Erreur chargement:', error);
        showNotification(`Erreur: ${error.message}`, 'error');
    }
}

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
            setTimeout(() => location.reload(), 1000);
        } else {
            const errorMsg = result.errors?.join('\n') || 'Erreur inconnue';
            showNotification(errorMsg, 'error');
        }

    } catch (error) {
        console.error('Erreur sauvegarde:', error);
        showNotification(`Erreur: ${error.message}`, 'error');
    }
}

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

function showNotification(message, type = 'info') {
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

function generateSlug(text) {
    return text
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

document.addEventListener('DOMContentLoaded', () => {
    const titleInput = document.getElementById('article-title');
    if (titleInput) {
        titleInput.addEventListener('input', (e) => {
            const slug = generateSlug(e.target.value);
            document.getElementById('generated-id').textContent = slug || '--';
        });
    }

    const addBlockBtn = document.getElementById('add-block-trigger');
    if (addBlockBtn) {
        addBlockBtn.addEventListener('click', () => {
            const type = document.getElementById('new-block-type').value;
            addBlock(type);
        });
    }

    const saveBtn = document.getElementById('save-article-btn');
    if (saveBtn) {
        saveBtn.addEventListener('click', saveArticle);
    }

    const newBtn = document.getElementById('new-article-btn');
    if (newBtn) {
        newBtn.addEventListener('click', resetEditor);
    }

    document.querySelectorAll('.load-article-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const filename = link.dataset.filename;
            if (confirm('Charger cet article ? Les modifications non sauvegardées seront perdues.')) {
                loadArticle(filename);
            }
        });
    });

    document.querySelectorAll('.btn-delete-file').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const filename = btn.dataset.filename;
            deleteArticle(filename);
        });
    });
});

```

## /json/articles/test-nucleus.json (fichier de test)
```json
{
    "type": "article",
    "meta": {
        "id": "test-nucleus",
        "created": "2025-01-15",
        "updated": "2025-01-15",
        "status": "draft",
        "author": "admin"
    },
    "content": [
        {
            "type": "title",
            "level": 2,
            "data": {
                "fr": "Test Nucleus",
                "en": "Nucleus Test"
            }
        },
        {
            "type": "text",
            "data": {
                "fr": "Ceci est un paragraphe de test.",
                "en": "This is a test paragraph."
            }
        }
    ]
}

```
## /json/config.json (mise à jour)

```json
{
    "singlepage": false,
    "titleWebsite": ["mascarade", "-bdx", ".fr"],
    "repImg": "img/content",
    "repImgDeco": "img/deco",
    "repJson": "../json/events/",
    "langs": [
        {"fr": "Français"},
        {"en": "English"}
    ]
}
```