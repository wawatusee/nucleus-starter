<?php
/**
 * PageRenderer - Résout et affiche un layout de page Nucleus
 *
 * Lit json/pages/{id}.json, résout chaque référence du layout
 * et délègue le rendu au renderer approprié.
 */

require_once __DIR__ . '/article_renderer.php';
require_once __DIR__ . '/../utils/json_handler.php';

class PageRenderer
{
    private string $pagesDir;
    private string $articlesDir;
    private string $galleriesDir;
    private string $lang;

    public function __construct(string $lang = 'fr')
    {
        $this->pagesDir    = ROOT_PATH . 'json/pages/';
        $this->articlesDir = ROOT_PATH . 'json/articles/';
        $this->galleriesDir = ROOT_PATH . 'public/img/content/galleries/';
        $this->lang        = $lang;
    }

    /**
     * Charge et affiche une page complète
     */
    public function render(string $pageId): void
    {
        $path = $this->pagesDir . $pageId . '.json';

        if (!file_exists($path)) {
            $this->renderNotFound($pageId);
            return;
        }

        try {
            $page = JsonHandler::load($path);
        } catch (Exception $e) {
            $this->renderError($e->getMessage());
            return;
        }

        if (empty($page['layout']) || !is_array($page['layout'])) {
            return; // Page vide, rien à afficher
        }

        foreach ($page['layout'] as $entry) {
            $this->renderEntry($entry);
        }
    }

    /**
     * Dispatche le rendu selon le type de référence
     */
    private function renderEntry(array $entry): void
    {
        switch ($entry['type'] ?? '') {
            case 'article_ref':
                $this->renderArticleRef($entry);
                break;

            case 'gallery_ref':
                $this->renderGalleryRef($entry);
                break;

            case 'ui_component':
                $this->renderUiComponent($entry);
                break;

            default:
                // Type inconnu : silencieux en prod
                break;
        }
    }

    /**
     * Rendu d'un article référencé
     */
    private function renderArticleRef(array $entry): void
    {
        $filename = $entry['filename'] ?? null;
        if (!$filename) return;

        $path = $this->articlesDir . $filename;

        if (!file_exists($path)) {
            // Fallback silencieux
            return;
        }

        try {
            $article = JsonHandler::load($path);
            ArticleRenderer::render($article, $this->lang);
        } catch (Exception $e) {
            // Silencieux en prod
        }
    }

    /**
     * Rendu d'une galerie photo
     */
    private function renderGalleryRef(array $entry): void
    {
        $folder = $entry['folder'] ?? null;
        if (!$folder) return;

        $dir = $this->galleriesDir . $folder . '/';
        if (!is_dir($dir)) return;

        $images = array_values(array_filter(
            scandir($dir),
            fn($f) => preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $f)
        ));

        if (empty($images)) return;

        echo '<section class="nucleus-gallery" data-folder="' . htmlspecialchars($folder) . '">' . "\n";
        echo '  <div class="gallery-grid">' . "\n";
        foreach ($images as $img) {
            $src = '/public/img/content/galleries/' . $folder . '/' . $img;
            echo '    <figure class="gallery-item">' . "\n";
            echo '      <img src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($img) . '" loading="lazy">' . "\n";
            echo '    </figure>' . "\n";
        }
        echo '  </div>' . "\n";
        echo '</section>' . "\n";
    }

    /**
     * Rendu d'un composant UI nommé
     */
    private function renderUiComponent(array $entry): void
    {
        $name = $entry['name'] ?? null;
        if (!$name) return;

        $tplPath = ROOT_PATH . 'public/components/' . $name . '.php';
        if (file_exists($tplPath)) {
            require $tplPath;
        }
    }

    private function renderNotFound(string $pageId): void
    {
        echo '<section class="nucleus-error"><p>Page introuvable : ' . htmlspecialchars($pageId) . '</p></section>';
    }

    private function renderError(string $msg): void
    {
        echo '<section class="nucleus-error"><p>Erreur de chargement.</p></section>';
    }
}
