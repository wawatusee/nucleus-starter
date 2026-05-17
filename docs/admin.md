# Administration — Documentation Nucleus

> Interface d'administration découplée du front.  
> Produit les JSON consommés par le public.

---

## Philosophie

- L'admin **connaît le front** — elle charge `config.php` via `config_admin.php`
- Le front **ne connaît pas l'admin** — jamais l'inverse
- Mêmes conventions que le public : `DIR_*`, BEM, `htmlspecialchars`, zéro `../` en dur

---

## Architecture

```
admin/
├── api/
│   ├── create_page_file.php  ← crée inc/pages/{id}.php standard
│   ├── delete_article.php
│   ├── delete_image.php
│   ├── delete_page.php
│   ├── get_article.php
│   ├── get_page.php
│   ├── list_articles.php
│   ├── list_images.php
│   ├── list_pages.php
│   ├── rename_image.php
│   ├── save_article.php
│   ├── save_menus.php        ← sauvegarde menus.json + crée pages manquantes
│   ├── save_page.php
│   └── upload_image.php
├── css/
│   ├── admin.css             ← classes génériques partagées
│   ├── login.css
│   └── pages/
│       ├── articles.css
│       ├── medias.css
│       ├── medias_images.css
│       ├── menus.css
│       └── pages.css
├── inc/
│   ├── footer.php
│   ├── head.php
│   ├── header.php
│   └── main.php
├── js/
│   ├── article_editor.js
│   └── page_builder.js
├── pages/
│   ├── articles.php
│   ├── dashboard.php
│   ├── medias.php
│   ├── medias_images.php
│   ├── menus.php
│   └── pages.php
├── src/
│   ├── folder_manager.class.php
│   ├── gallery_manager.class.php  ← à auditer
│   ├── image_uploader.class.php
│   └── model/
│       └── config_model.php       ← doublon — à supprimer
├── tests/
│   ├── test_api_v2.php
│   ├── test_audit.php
│   ├── test_block_registry.php
│   ├── test_component_model.php
│   └── test_json_handler.php
├── config_admin.php
├── index.php
├── login.php
└── login.class.php
```

**Fichiers partagés front/admin (dans `src/`) :**
- `src/core/component_model.php` — CRUD générique articles
- `src/core/page_model.php` — CRUD pages
- `src/core/block_registry.php` — types : title, text, list, link, image
- `src/model/config_model.php` — source unique langues et config
- `src/utils/json_handler.php` — lecture/écriture JSON atomique

---

## Configuration — `config_admin.php`

**Convention** : tout fichier admin charge `config_admin.php` en première ligne.

### Constantes exposées

| Constante | Valeur |
|---|---|
| `ADMIN_PATH` | Chemin absolu vers `/admin/` |
| `JSON_PAGES_DIR` | `DIR_JSON . 'pages/'` |
| `JSON_ARTICLES_DIR` | `DIR_JSON . 'articles/'` |
| `GALLERIES_DIR` | `DIR_IMG_CONTENT . 'galleries/'` |
| `ADMIN_PAGES` | `['dashboard', 'pages', 'articles', 'medias', 'medias_images', 'menus']` |
| `SESSION_LIFETIME` | `3600` |
| `UPLOAD_MAX_SIZE` | `2 Mo` |
| `UPLOAD_ALLOWED_TYPES` | `jpeg, png, webp` |

---

## Modèles

### `ConfigModel` — `src/model/config_model.php`
- `getLangs()` → `[['code' => 'fr', 'label' => 'Français'], ...]`
- `getDefaultLang()` → `$langs[0]['code'] ?? 'fr'`
- Source de vérité pour les langues — pilote l'éditeur de menus et les blocs multilingues

> `admin/src/model/config_model.php` est un doublon — à supprimer.

### `ComponentModel` — `src/core/component_model.php`
- `new ComponentModel($storageDir, $langs, $componentType)`
- `$langs` → `array_column(ConfigModel::getLangs(), 'code')`

### `PageModel` — `src/core/page_model.php`
- Types de références : `article_ref`, `gallery_ref`
- `createEmpty($title)` — page vide en `draft`
- `exists($id)` — vérifié par `save_menus.php` avant création

### `FolderManager` — `admin/src/folder_manager.class.php`
- `create($name)` — répertoire + `thumbs/` systématique
- `rename`, `delete`, `list`, `exists`

### `ImageUploader` — `admin/src/image_uploader.class.php`
- Retourne `['base' => 'home/photo', 'ext' => 'jpg']`
- Grand format 1280px + miniature 400px
- Conversion JPG systématique

---

## API — `admin/api/`

**Articles**

| Fichier | Méthode | Rôle |
|---|---|---|
| `list_articles.php` | GET | Liste les articles |
| `get_article.php` | GET | Charge un article |
| `save_article.php` | POST | Crée ou met à jour |
| `delete_article.php` | POST | Supprime |

**Pages**

| Fichier | Méthode | Rôle |
|---|---|---|
| `list_pages.php` | GET | Liste les layouts |
| `get_page.php` | GET | Charge un layout |
| `save_page.php` | POST | Crée ou met à jour |
| `delete_page.php` | POST | Supprime |
| `create_page_file.php` | POST | Crée `inc/pages/{id}.php` standard |

**Menus**

| Fichier | Méthode | Rôle |
|---|---|---|
| `save_menus.php` | POST | Sauvegarde `menus.json` + crée pages manquantes |

**Images**

| Fichier | Méthode | Rôle |
|---|---|---|
| `list_images.php` | GET | Liste répertoires ou images |
| `upload_image.php` | POST | Upload + resize |
| `delete_image.php` | POST | Supprime original + thumb |
| `rename_image.php` | POST | Renomme original + thumb |

---

## Menus — `menus.php` + `save_menus.php`

- Deux sections sur une page — `Main_menu` et `RS_menu`
- Langues pilotées par `config.json` — les langues orphelines sont perdues à la sauvegarde
- Réordonnement par boutons ↑↓
- **Création automatique** — si une entrée `Main_menu` n'a pas de `json/pages/{page}.json`, `save_menus.php` crée la page vide en `draft` via `PageModel::createEmpty()`
- La réponse inclut `created` — liste des pages créées, affichée dans le feedback JS

---

## Pages — `pages.php` + `create_page_file.php`

- Sidebar liste les layouts `json/pages/`
- Indicateur par page : `📄` si `inc/pages/{page}.php` absent, `✓` si présent
- Clic `📄` → `create_page_file.php` → crée le fichier standard sans rechargement
- Le fichier créé contient l'appel `PageRenderer::render('{page}')` — remplaçable par du PHP libre

**Coexistence des deux modèles :**
```
inc/main.php
  → inc/pages/{page}.php existe ? → charge le fichier PHP (logique libre)
  → sinon                          → PageRenderer depuis json/pages/{page}.json
```

---

## Bloc image — format JSON

```json
{
    "type": "image",
    "src": "home/photo.jpg",
    "alt": "Description"
}
```

- `src` — chemin relatif depuis `public/img/content/`
- `dataType: null` dans `BlockRegistry` — pas de champ `data` multilingue
- Rendu : `<img class="nucleus-image" src="..." alt="..." loading="lazy">`

---

## Module contacts — fermé

Coordonnées gérées via articles standards. API, page et JS contacts supprimés.

---

## Langues

```php
$langKeys = array_column(ConfigModel::getLangs(), 'code');

foreach (ConfigModel::getLangs() as $langue) {
    $langue['code'];
    $langue['label'];
}
```

---

## Sessions

```php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

---

## CSS

| Fichier | Rôle |
|---|---|
| `admin.css` | Layout, boutons, inputs, messages — toutes pages |
| `pages/articles.css` | Éditeur articles |
| `pages/medias.css` | Noms de répertoires |
| `pages/medias_images.css` | Thumbnails, renommage |
| `pages/pages.css` | Page builder |

---

## JavaScript

### `article_editor.js`
- Types : `title`, `text`, `list`, `link`, `image`
- Navigateur médias — modale, `list_images.php`, sélection → `block-src`
- `API_BASE = 'api/'`

### `page_builder.js`
- Classe `PageEditor`, registry pattern
- `window.availableGalleries` injecté par `pages.php`

---

## Ce qui a été fait

### Session 4 — 2026-05-08
- Audit `config_admin.php`, `config_model.php`
- Correction langue `code`/`label`
- Cartographie arborescence

### Session 5 — 2026-05-10
- Application corrections — config, session, chemins
- Migration `api/v2/` → `api/`
- Fermeture module contacts
- Tests complets ✅

### Session 6 — 2026-05-16
- `FolderManager`, `ImageUploader` réécrits
- Pages `medias.php`, `medias_images.php`
- API images complète
- Bloc `image` — `BlockRegistry`, `ArticleRenderer`, `article_editor.js`
- Navigateur médias dans l'éditeur
- CSS factorisé

### Session 7 — 2026-05-16
- Éditeur de menus `menus.php` + `save_menus.php`
- Création automatique des pages à la sauvegarde des menus
- `pages.php` — indicateur `📄` / `✓` + `create_page_file.php`
- Coexistence PHP libre / PageRenderer documentée

---

## Ce qu'il reste à faire

### Court terme
- [ ] Supprimer `admin/src/model/config_model.php` — doublon
- [ ] Déplacer modale médias hors du `<form>` dans `articles.php`
- [ ] Nettoyer `admin/pages/galleries.php` — bloc session commenté

### Moyen terme
- [ ] Auditer `admin/tests/`
- [ ] Auditer `gallery_manager.class.php`
- [ ] Migrer CSS inline `showNotification()` vers classes
- [ ] Sécuriser uploads — vérification MIME réelle
- [ ] `login.php` — nettoyer HTML, passer en français
- [ ] Page "Configuration" — éditer `config.json` (titre, langues)

---

## Ambitions — pistes ouvertes

### Routing automatique
Fallback dans `inc/main.php` — si aucun `.php` dédié, `PageRenderer` prend le relais. Simplifie la suppression de pages.

### PHP libre dans l'éditeur
Éditer le contenu de `inc/pages/{page}.php` directement depuis l'admin — phase 2 de `create_page_file.php`.

### Brouillons
`status: draft` dans le modèle — logique front à implémenter.

---

## Tests — `admin/tests/`

| Fichier | Composant |
|---|---|
| `test_api_v2.php` | Endpoints API |
| `test_audit.php` | Audit général |
| `test_block_registry.php` | `BlockRegistry` |
| `test_component_model.php` | `ComponentModel` |
| `test_json_handler.php` | `JsonHandler` |

---

*Dernière mise à jour : session 7 — 2026-05-16*  
*Prochaine session : nettoyage vestiges + routing automatique.*
