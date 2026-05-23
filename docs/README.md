# NUCLEUS — Manifeste & Documentation

> Framework PHP procédural, headless, multilingue et réutilisable.  
> Pensé pour être compris d'un coup d'œil, repris sans douleur.

---

## Philosophie

**Nucleus** est un socle de site web PHP sans framework, sans POO forcée, sans magie cachée.

Les choix structurants :

- **Procédural organisé** — pas de MVC, pas d'orienté objet imposé. La logique est lisible de haut en bas.
- **JSON comme base de données** — les contenus (articles, pages, menus, config) vivent en fichiers JSON. Pas de BDD relationnelle, pas de dépendance serveur lourde.
- **CMS headless** — une interface d'administration découplée du front. Le front consomme les JSON, l'admin les produit.
- **Zéro framework** — pas de Composer, pas de React, pas de Laravel. PHP natif, JS natif, CSS natif.
- **Réutilisable** — la structure est conçue pour être dupliquée et adaptée à d'autres projets en ne touchant qu'à la configuration.

---

## Architecture générale

```
/
├── config/               ← Configuration (technique + métier)
├── docs/                 ← Documentation du projet
├── inc/                  ← Includes front (head, header, main, footer)
├── json/                 ← Base de données JSON
│   ├── articles/
│   ├── galleries/        ← Métadonnées des galeries (titre, alt, caption)
│   ├── pages/
│   └── menus.json
├── public/               ← Seul dossier exposé au web
│   ├── index.php         ← Point d'entrée unique
│   ├── css/
│   ├── js/
│   │   └── pages/        ← JS spécifique par page (chargé si existant)
│   └── img/
│       ├── content/      ← Images de contenu, organisées par répertoire
│       │   └── {dir}/thumbs/  ← Miniatures générées à l'upload
│       └── deco/         ← logo.svg et icônes RS
├── src/                  ← Logique métier
│   ├── core/
│   ├── model/
│   ├── utils/
│   └── view/
├── admin/                ← Interface d'administration
│   ├── api/              ← Endpoints JSON (lecture/écriture)
│   ├── css/
│   ├── inc/              ← Layout admin (head, header, main, footer)
│   ├── js/               ← Éditeurs JS
│   ├── pages/            ← Fragments de pages admin
│   └── src/              ← Classes utilitaires admin
└── tests/                ← Scripts de test par composant
```

> **Règle absolue** : seul `/public/` est accessible depuis le web. Tout le reste est hors racine publique.

---

## Décisions architecturales

### `$singlePage` déprécié
Le mode single-page scroll (2015-2020) est abandonné. Aucun projet actif ne l'utilise. SEO, performance et accessibilité favorisent le multi-pages.

Supprimé de : `config.json`, `config.php`, `config_model.php`, `main.php`, `header.php`, `view_menus.php`.  
`inc/nav.php` supprimé — absorbé dans `header.php`.

### Module contacts fermé
Les coordonnées de contact sont des articles comme les autres — `contact-coordonnees.json` fonctionne via `ArticleRenderer` avec des blocs `text` et `link`.

Supprimé : `json/contacts/`, `admin/api/get_contacts_list.php`, `admin/api/save_contact.php`, `admin/pages/contacts.php`, `admin/js/contact_editor.js`.

Exception future : si un formulaire d'envoi de message est envisagé, il nécessitera un composant dédié avec traitement PHP.

### `GalleryManager` abandonné
L'ancienne classe `GalleryManager` (logique `STRTOUPPER`, `galleries_index.json`, dossiers `original/`) est remplacée par :
- `admin/api/save_gallery.php` — écriture dans `json/galleries/{folder}.json`
- `ImageUploader` — upload + redimensionnement + génération de miniature
- `FolderManager` — création/renommage/suppression de répertoires physiques
- `PageRenderer::renderGalleryRef()` — rendu côté front (mode riche JSON ou mode simple scan)

### Hiérarchie des titres
Les articles commencent à `h2` — le `h1` appartient à la page, pas aux articles. Le niveau est piloté par le JSON (`"level": 2`). Le CSS qualifie par niveau HTML (`h2.nucleus-title`, `h3.nucleus-title`) pour préserver l'indépendance de chaque niveau.

### Format `data` unifié
Tous les blocs de contenu multilingue stockent leurs données sous la clé `data` :

```json
{ "type": "text", "data": { "fr": "Bonjour", "en": "Hello" } }
{ "type": "list", "data": { "fr": ["A", "B"], "en": ["A", "B"] } }
```

Le type `image` fait exception — pas de `data` multilingue, uniquement `src` et `alt`.

### Langues — structure `config.json`
```json
"langs": [
    {"code": "fr", "label": "Français"},
    {"code": "en", "label": "Anglais"}
]
```

Les codes sont extraits par `array_column(ConfigModel::getLangs(), 'code')`.  
Le premier élément est la langue par défaut.  
Les endpoints API n'ont aucune langue codée en dur — tout passe par `ConfigModel`.

### Tests
Pas de framework de test. Convention : un fichier `tests/test_*.php` par composant critique, écrit au moment de la correction. Zéro dépendance externe.

Fichiers de test existants :
- `tests/test_menus_model.php` ✓
- `tests/test_view_menus.php` ✓
- `tests/test_config_model.php` ✓

---

## Configuration

### `config/config.json` — Config métier (éditable via l'admin)

```json
{
    "titleWebsite": ["mon-", "site", ".fr"],
    "repImg": "content",
    "repImgDeco": "deco",
    "langs": [
        {"code": "fr", "label": "Français"},
        {"code": "en", "label": "Anglais"}
    ]
}
```

- `langs` : liste des langues disponibles, le premier est la langue par défaut
- Les chemins (`repImg`, `repImgDeco`) sont des **clés logiques** — les chemins absolus sont construits par `config.php`

### `config/config.php` — Config technique (dev uniquement)

Responsabilités :
- Définir toutes les constantes de chemins (`ROOT_PATH`, `DIR_JSON`, `DIR_IMG_CONTENT`...)
- Charger et parser `config.json` via `ConfigModel`
- Dériver les constantes (`SITE_TITLE`, `LANG_DEFAULT`, `PAGE_ARRAY`...)
- Instancier les modèles (`MenusModel`)

**Convention de nommage des constantes :**

| Préfixe | Usage |
|---|---|
| `DIR_*` | Chemins absolus serveur |
| `PUBLIC_*` | Chemins relatifs navigateur |
| `APP_*` | État de l'application (langue courante...) |

### `config/config_admin.php` — Config admin

- Inclut `config.php` en premier — l'admin connaît le front, pas l'inverse
- Session démarrée ici via `session_status()` — jamais dans les endpoints ou pages
- Ajoute : `ADMIN_PATH`, `JSON_PAGES_DIR`, `JSON_ARTICLES_DIR`, `JSON_GALLERIES_DIR`, `GALLERIES_DIR`
- Limites upload : `UPLOAD_MAX_SIZE` (2 Mo), `UPLOAD_ALLOWED_TYPES` (`jpeg`, `png`, `webp`)

**Convention** : tout fichier admin charge `config_admin.php` en première ligne — jamais `config.php` directement.

---

## Point d'entrée — `public/index.php`

Séquence d'exécution :

```
1. require config.php        ← chemins, constantes, modèles, menus
2. Détection de $lang        ← ?lang=fr, fallback sur LANG_DEFAULT
3. Détection de $page        ← ?page=home, fallback sur premier menu, whitelist PAGE_ARRAY
4. HTML / head.php           ← balises meta, CSS, title
5. header.php                ← logo, nav, sélecteur de langue, burger
6. main.php                  ← contrôleur central, chargement de la page
7. footer.php                ← pied de page
```

### Détection de langue

```php
if (isset($_GET['lang']) && in_array($_GET['lang'], array_column($langs, 'code'))) {
    $lang = $_GET['lang'];
} else {
    $lang = LANG_DEFAULT;
}
define('APP_LANG', $lang);
```

### Détection de page

```php
if (isset($_GET['page']) && in_array($_GET['page'], PAGE_ARRAY)) {
    $page = htmlspecialchars($_GET['page']);
} else {
    $page = $defaultPage;
}
```

`PAGE_ARRAY` est construit depuis `menus.json` — la whitelist est vivante, pas codée en dur.

---

## Contrôleur central — `inc/main.php`

`$page` est détecté et validé en amont dans `public/index.php`.  
`PAGE_ARRAY` est construit depuis `menus.json` via `config.php`.

```php
if (in_array($page, PAGE_ARRAY)) {
    require_once __DIR__ . '/pages/' . $page . '.php';
} else {
    require_once __DIR__ . '/pages/404.php';
}
```

---

## Header & Navigation — `inc/header.php`

### Structure HTML

```
<header class="site-header">
    <a class="site-header__logo">        ← logo SVG en <img>
    <nav class="site-nav" id="siteNav">  ← menu principal
    <div class="site-header__controls">  ← langue + burger
```

### Conventions BEM

| Classe | Rôle |
|---|---|
| `.site-header` | Block header |
| `.site-header__logo` | Element logo |
| `.site-header__controls` | Element contrôles |
| `.site-nav` | Block nav |
| `.site-nav--open` | Modifier nav ouverte (mobile) |
| `.nav__link` | Element lien de nav |
| `.nav__link--active` | Modifier lien actif |
| `.lang-switcher` | Block sélecteur de langue |
| `.lang--active` | Modifier langue active |
| `.burger` | Block burger |
| `.burger--open` | Modifier burger ouvert |
| `.burger__bar` | Element barre du burger |

### Variables locales — `header.css`

```css
--header-direction:   row;
--header-justify:     space-between;
--header-align:       center;
--header-gap:         var(--spacing-sm);
--header-padding:     var(--spacing-xs) var(--spacing-md);
--header-logo-height: 52px;
--header-bg:          var(--color-primary);
```

### Logo
Fichier : `public/img/deco/logo.svg`  
Intégration : balise `<img>` — pas de SVG inline.

### Sélecteur de langue
Construction dynamique avec `http_build_query` — conserve tous les paramètres GET existants en remplaçant uniquement `lang`.

### Burger mobile
Animé en CSS pur (trois `<span>` → croix). Le JS toggle uniquement les classes `.site-nav--open` et `.burger--open` et l'attribut `aria-expanded`.

---

## Footer — `inc/footer.php`

- Données de contact chargées depuis `json/articles/contact-coordonnees.json`
- Menu footer via `ViewMenu(APP_LANG, '')` — pas de lien actif
- `RS_menu` chargé depuis `$menuRS` disponible via `config.php`
- Logo centré en `<img>` — pas de SVG inline
- Grille auto-répartie — `repeat(auto-fit, minmax(200px, 1fr))`

### Variables locales — `footer.css`

```css
--footer-bg:          var(--color-primary);
--footer-color:       white;
--footer-link-color:  rgba(255, 255, 255, 0.8);
--footer-logo-height: 64px;
--footer-padding:     var(--spacing-lg) var(--spacing-md);
--footer-gap:         var(--spacing-lg);
--footer-col-min:     200px;
```

### Classes BEM

| Classe | Rôle |
|---|---|
| `.site-footer` | Block footer |
| `.site-footer__grid` | Grille auto-répartie |
| `.site-footer__bloc` | Colonne générique |
| `.site-footer__title` | Titre de bloc |
| `.site-footer__text` | Texte |
| `.site-footer__link` | Lien de contact |
| `.site-footer__rs` | Bloc réseaux sociaux |
| `.site-footer__logo` | Logo centré |
| `.rs-link` | Lien RS — cercle cliquable |
| `.rs-icon--{nom}` | Icône RS via background-image |

---

## Modèles — `src/model/`

### `ConfigModel`
- Pattern cache statique — `loadConfig()` ne lit le fichier qu'une fois
- `getLangs()` retourne `[['code' => 'fr', 'label' => 'Français'], ...]`
- `getDefaultLang()` retourne `$langs[0]['code']`
- `getTitle()` retourne le titre depuis `titleWebsite`
- `isSinglePage()` supprimée
- `clearCache()` disponible pour les tests
- Utilisable en contexte front (`ROOT_PATH` défini par `config.php`) et admin (`config_admin.php`)
- `tests/test_config_model.php` ✓

### `MenusModel`
- Charge `json/menus.json`
- `getMenu(string $menuType)` retourne le tableau du menu demandé
- Exceptions explicites si fichier absent ou JSON malformé
- `tests/test_menus_model.php` ✓

---

## Vue — `src/view/view_menus.php`

### `ViewMenu`

```php
new ViewMenu(APP_LANG, $page)   // header — lien actif
new ViewMenu(APP_LANG, '')      // footer — pas de lien actif
```

- `$lang` et `$currentPage` définis au constructeur
- `getViewMainMenu(array $menuArray): string`
  - Génère les liens `.nav__link`
  - Ajoute `.nav__link--active` sur la page courante
  - Fallback titre : langue demandée → `fr` → `$item->page`
  - `htmlspecialchars` sur le label
- `tests/test_view_menus.php` ✓

---

## Core — `src/core/`

### `BlockRegistry`
- Définit les types de blocs : `title`, `text`, `list`, `link`, `image`
- Chaque type déclare : `label`, `fields` (champs spécifiques), `dataType` (`string`, `array`, ou `null`)
- `dataType: null` pour le type `image` — pas de champ `data` multilingue
- `validate(array $block, array $langs)` — retourne `['valid', 'errors']`
- `normalize(array $block, array $langs)` — applique les valeurs par défaut, initialise `data` pour les langues manquantes
- La validation `data` est ignorée si `dataType === null`

### `ComponentModel`
- CRUD générique pour tout composant `{ type, meta, content[] }`
- Utilisé pour les articles — extensible à d'autres types
- `save()` appelle `BlockRegistry::normalize()` puis `BlockRegistry::validate()` sur chaque bloc
- `listAll(bool $withMeta)` — sans meta : liste de fichiers ; avec meta : id, status, updated, blocksCount
- `generateId(string $title)` — slugification sans dépendance externe

### `PageModel`
- CRUD pour les pages `{ type, meta, layout[] }`
- Le layout référence des composants existants — pas de blocs directs
- Types autorisés dans le layout : `article_ref`, `gallery_ref` (constante `ALLOWED_REF_TYPES`)
- `article_ref` requiert `filename`, `gallery_ref` requiert `folder`
- `createEmpty(string $title)` — crée une page vide avec id slugifié
- `save_menus.php` appelle `createEmpty()` automatiquement pour les pages absentes

### `PageRenderer`
- Charge `json/pages/{id}.json` et dispatche chaque entrée du layout
- `article_ref` → `ArticleRenderer::render()`
- `gallery_ref` → rendu inline avec deux modes :
  - **Mode riche** : lit `json/galleries/{folder}.json` (titre ML, alt, caption par image)
  - **Mode simple** : scan du dossier `thumbs/`
- `ui_component` → `require` de `public/components/{name}.php`
- Erreurs loggées via `error_log` — silencieuses en prod

### `ArticleRenderer`
- Rendu statique par blocs : `title`, `text`, `list`, `link`, `image`
- Méthode `t()` — fallback langue demandée → `fr` → `en` → chaîne vide
- Bloc `image` : rendu `<img class="nucleus-image">` avec `loading="lazy"`
- Pas de `data` multilingue pour `image` — `src` et `alt` directs

### `JsonHandler`
- Lecture sécurisée avec exceptions explicites
- Écriture atomique via fichier `.tmp` → `rename()`
- `listFiles`, `exists`, `delete` disponibles

---

## Admin — `admin/`

### APIs — `admin/api/`

Tous les endpoints suivent la même convention :
- `require_once __DIR__ . '/../config_admin.php'` en première ligne
- Vérification `$_SESSION['user']` → 401 si absent
- `header('Content-Type: application/json; charset=utf-8')`
- Réponse `{ success, errors?, ... }`

| Fichier | Méthode | Rôle |
|---|---|---|
| `save_article.php` | POST | Sauvegarde un article via `ComponentModel` |
| `get_article.php` | GET `?file=` | Charge un article |
| `list_articles.php` | GET `?meta=1` | Liste les articles |
| `delete_article.php` | POST | Supprime un article |
| `save_page.php` | POST | Sauvegarde un layout de page via `PageModel` |
| `get_page.php` | GET `?file=` | Charge un layout |
| `list_pages.php` | GET `?meta=1` | Liste les pages |
| `delete_page.php` | POST | Supprime une page |
| `create_page_file.php` | POST | Crée `inc/pages/{id}.php` standard |
| `save_gallery.php` | POST | Sauvegarde `json/galleries/{folder}.json` |
| `get_gallery.php` | GET `?folder=` | Charge une galerie |
| `list_galleries.php` | GET | Liste les galeries JSON |
| `delete_gallery.php` | POST | Supprime une galerie JSON |
| `list_images.php` | GET `?dir=` | Sans dir : liste les répertoires ; avec dir : liste les images |
| `upload_image.php` | POST | Upload via `ImageUploader` (grand format + miniature) |
| `delete_image.php` | POST | Supprime image + miniature |
| `rename_image.php` | POST | Renomme image + miniature avec slugification |
| `save_menus.php` | POST | Sauvegarde `menus.json`, crée les pages manquantes |

### Classes utilitaires — `admin/src/`

#### `ImageUploader`
- Entrée : `$_FILES` + nom de répertoire
- Sortie : `['base' => 'dir/slug', 'ext' => 'jpg']`
- Génère deux fichiers : grand format (max 1280px) et miniature (max 400px)
- Convertit tout en JPEG — fond blanc pour les PNG/WebP transparents
- Slugification du nom de fichier à l'upload

#### `FolderManager`
- Crée, renomme, supprime et liste les répertoires de contenu
- `create()` génère automatiquement le sous-dossier `thumbs/`
- Suppression récursive via `deleteRecursive()`
- Pas de logique d'image, pas d'index JSON

### Pages admin — `admin/pages/`

| Page | Rôle |
|---|---|
| `dashboard.php` | Liens vers les sections |
| `articles.php` | Éditeur d'articles — sidebar + workspace |
| `pages.php` | Éditeur de layouts de pages — sidebar + builder |
| `galleries.php` | Gestion des galeries JSON |
| `medias.php` | Gestion des répertoires d'images |
| `medias_images.php` | Upload, renommage, suppression d'images |
| `menus.php` | Éditeur de menus principal et RS |

### Éditeurs JS — `admin/js/`

#### `article_editor.js`
Éditeur de blocs pour les articles. Responsabilités actuelles :
- `BlockTemplates` — templates HTML par type de bloc (`title`, `text`, `list`, `link`, `image`)
- `generateLangInputs()` — champs multilingues par langue active
- `createBlockWrapper()` — enveloppe bloc avec contrôles (monter/descendre/supprimer)
- `collectArticleData()` — sérialise le workspace au format JSON v2
- `loadArticle()` / `saveArticle()` / `deleteArticle()` — appels API
- `switchEditorLang()` — bascule les champs visibles
- Media browser — modale de sélection d'images avec chargement des répertoires et prévisualisation

#### `page_builder.js`
Éditeur de layouts de pages (`article_ref`, `gallery_ref`, `ui_component`).

---

## Schémas JSON

### Article

```json
{
    "type": "article",
    "meta": {
        "id": "mon-article",
        "created": "2026-01-01",
        "updated": "2026-05-23",
        "status": "draft",
        "author": "admin"
    },
    "content": [
        { "type": "title", "level": 2, "data": { "fr": "Titre", "en": "Title" } },
        { "type": "text",  "data": { "fr": "Texte.", "en": "Text." } },
        { "type": "list",  "data": { "fr": ["A", "B"], "en": ["A", "B"] } },
        { "type": "link",  "url": "https://example.com", "data": { "fr": "Voir", "en": "See" } },
        { "type": "image", "src": "home/photo.jpg", "alt": "Description" }
    ]
}
```

### Page

```json
{
    "type": "page",
    "meta": { "id": "home", "created": "2026-01-01", "updated": "2026-05-23", "status": "draft" },
    "layout": [
        { "type": "article_ref", "filename": "intro.json" },
        { "type": "gallery_ref", "folder": "evenements", "gallery": "evenements" },
        { "type": "ui_component", "name": "hero" }
    ]
}
```

### Galerie

```json
{
    "type": "gallery_ref",
    "folder": "evenements",
    "title": { "fr": "Événements", "en": "Events" },
    "images": [
        {
            "src": "photo.jpg",
            "alt": { "fr": "Description", "en": "Description" },
            "caption": { "fr": "Légende", "en": "Caption" }
        }
    ]
}
```

---

## Design System CSS

### Hiérarchie des feuilles de style

| Niveau | Fichier | Rôle |
|---|---|---|
| 1 | `style.css` | Variables globales, reset, typo, utilitaires |
| 2 | `header.css` | Header + nav |
| 2 | `main.css` | Contenant principal + blocs nucleus |
| 2 | `footer.css` | Pied de page |
| 3 | `pages/{page}.css` | Surcharges spécifiques à une page |

Chaque niveau 2 expose ses **variables locales** en tête — un seul endroit à modifier pour reconfigurer.

### Classes nucleus — produites par `ArticleRenderer`

| Classe | Rôle |
|---|---|
| `.nucleus-article` | Conteneur article |
| `h1.nucleus-title` | Titre niveau 1 — accent border-bottom |
| `h2.nucleus-title` | Titre niveau 2 |
| `h3.nucleus-title` | Titre niveau 3 |
| `h4-6.nucleus-title` | Titres mineurs |
| `.nucleus-text` | Paragraphe |
| `.nucleus-link` | Lien ou bouton |
| `.nucleus-list` | Liste à puces — marker accent |
| `.nucleus-image` | Image de contenu — lazy loading |
| `.nucleus-gallery` | Conteneur galerie |
| `.gallery-grid` | Grille masonry |
| `.gallery-item` | Figure individuelle |
| `.gallery-item__img` | Image de la figure |
| `.gallery-item__caption` | Légende optionnelle |

### Variables globales clés — `style.css`

```css
/* Couleurs */
--color-primary, --color-primary-dark, --color-accent
--color-bg, --color-surface, --color-text, --color-muted, --color-border

/* Typographie */
--font-base, --font-title
--fs-xs, --fs-sm, --fs-md, --fs-lg, --fs-xl

/* Espacement */
--spacing-xs, --spacing-sm, --spacing-md, --spacing-lg

/* Largeurs */
--width-content: 1100px
--width-wide: 1200px

/* Divers */
--radius, --transition
```

---

## Menus — `json/menus.json`

```json
{
    "Main_menu": [
        { "page": "home", "titre": { "fr": "Accueil", "en": "Home" } }
    ],
    "RS_menu": [
        { "page": "https://www.facebook.com/...", "titre": "facebook" }
    ]
}
```

- `Main_menu` alimente `PAGE_ARRAY` et la navigation principale
- `RS_menu` alimente les liens réseaux sociaux
- Les titres sont multilingues sur `Main_menu`, simples sur `RS_menu`

---

## Lancement serveur local

```powershell
php -S localhost:8000 -t public
```

- `-t public` expose uniquement `/public/` — cohérent avec la prod
- Ouvrir `http://localhost:8000` dans le navigateur

---

## Conventions à respecter

- **Chemins** : toujours des constantes `DIR_*` (absolus) ou `PUBLIC_*` (navigateur) — jamais de `../` en dur
- **Nommage CSS** : BEM — `.block__element--modifier`
- **Variables CSS** : chaque composant expose ses variables locales en tête de fichier
- **Titres** : les articles commencent à `h2` — `h1` appartient à la page
- **Nommage JS** : `camelCase`, `addEventListener` uniquement — pas de `onclick` inline
- **Sécurité** : toujours `htmlspecialchars()` sur les variables affichées, whitelist sur `$page`
- **Config** : une seule source de vérité — `config.json` pour le métier, `config.php` dérive les constantes
- **Tests** : tout point de friction corrigé → un `tests/test_*.php` associé
- **Erreurs** : jamais silencieuses — `error_log` minimum, exception explicite si critique
- **Langues** : `array_column(ConfigModel::getLangs(), 'code')` pour les codes — jamais hardcodé

---

## Ce qu'il reste à faire

### Court terme

- [x] **Bloc `image`** — `BlockRegistry`, `ArticleRenderer`, `article_editor.js`, modale media-browser ✓
- [ ] **Icônes RS** — SVG facebook et instagram dans `public/img/deco/`
- [ ] **Balises OG** — alimentées depuis le JSON de la page ou de l'article courant
- [ ] **`.htaccess`** — sécuriser `/config/`, `/json/`, `/src/`, `/admin/`
- [ ] **CSS admin** — migrer le CSS inline de `showNotification()` vers des classes
- [ ] **`login.php`** — nettoyer le HTML, passer en français

### Moyen terme

- [ ] **Uploads** — vérification MIME réelle côté serveur, pas seulement l'extension
- [ ] **Brouillons** — exploiter `status: draft` côté front (ne pas afficher les articles non publiés)
- [ ] **Balise `<title>` dynamique** — nourrie par le JSON de la page courante
- [ ] **Pages spécifiques** — CSS dédié pour `events`, `social`, `contact` si nécessaire

### Nucléarisation de `article_editor.js`

Le fichier dépasse 600 lignes et mêle plusieurs responsabilités. Une découpe naturelle serait :

- `editor-core.js` — état global (`activeLang`, `currentFilename`), `switchEditorLang()`, `generateSlug()`, initialisation DOM
- `editor-blocks.js` — `BlockTemplates`, `createBlockWrapper()`, `generateLangInputs()`, `addBlock()`, `moveBlock()`
- `editor-api.js` — `loadArticle()`, `saveArticle()`, `deleteArticle()`, `collectArticleData()`
- `editor-media.js` — modale media-browser, `loadMediaDirs()`, `loadMediaImages()`

Chaque module expose ses fonctions sur un objet global (`window.NucleusEditor`) ou via des événements custom — sans framework de modules pour rester cohérent avec la philosophie zéro-dépendance.

**Condition préalable** : définir l'ordre de chargement dans `articles.php` et s'assurer que `editor-core.js` est chargé en premier (il porte les variables partagées).

### Long terme — ambitions

- [ ] **Routing automatique** — fallback `PageRenderer` si pas de fichier `.php` dédié dans `inc/pages/`
- [ ] **Éditeur de menus amélioré** — créer une page et l'ajouter au menu en une seule opération
- [ ] **Internationalisation complète** — traductions des libellés admin par langue
- [ ] **Kit de démarrage** — template réutilisable vierge, sans contenu projet
- [ ] **Tests** — formaliser la couverture sur `ComponentModel`, `PageModel`, `BlockRegistry`

---

*Dernière mise à jour : session 7 — 2026-05-23*  
*Prochaine session : icônes RS + `.htaccess` + nucléarisation JS.*
