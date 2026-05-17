<?php
/**
 * ArticleRenderer - Affiche les blocs d'un article Nucleus
 *
 * Types de blocs supportés : title, text, list, link
 */

class ArticleRenderer
{
    /**
     * Affiche tous les blocs d'un article dans la langue demandée
     */
    public static function render(array $article, string $lang = 'fr'): void
    {
        $blocks = $article['content'] ?? [];

        if (empty($blocks))
            return;

        $id = $article['meta']['id'] ?? '';

        echo '<article class="nucleus-article" data-id="' . htmlspecialchars($id) . '">' . "\n";

        foreach ($blocks as $block) {
            self::renderBlock($block, $lang);
        }

        echo '</article>' . "\n";
    }

    /**
     * Dispatche le rendu d'un bloc selon son type
     */
    private static function renderBlock(array $block, string $lang): void
    {
        switch ($block['type'] ?? '') {
            case 'title':
                self::renderTitle($block, $lang);
                break;
            case 'text':
                self::renderText($block, $lang);
                break;
            case 'list':
                self::renderList($block, $lang);
                break;
            case 'link':
                self::renderLink($block, $lang);
                break;
            default:
                break;

            case 'image':
                self::renderImage($block);
                break;
        }
    }

    private static function renderTitle(array $block, string $lang): void
    {
        $text = self::t($block, $lang);
        $level = isset($block['level']) ? (int) $block['level'] : 2;
        $level = max(1, min(6, $level)); // sécurité h1-h6

        if (!$text)
            return;
        echo "<h{$level} class=\"nucleus-title\">" . htmlspecialchars($text) . "</h{$level}>\n";
    }

    private static function renderText(array $block, string $lang): void
    {
        $text = self::t($block, $lang);
        if (!$text)
            return;
        // Autorise le HTML basique (gras, italique, liens)
        echo '<p class="nucleus-text">' . nl2br(htmlspecialchars($text)) . "</p>\n";
    }

    private static function renderList(array $block, string $lang): void
    {
        $src = isset($block['data']) ? $block['data'] : $block;
        $items = $src[$lang] ?? $src['fr'] ?? [];
        if (empty($items))
            return;

        echo '<ul class="nucleus-list">' . "\n";
        foreach ($items as $item) {
            echo '  <li>' . htmlspecialchars($item) . "</li>\n";
        }
        echo "</ul>\n";
    }

    private static function renderLink(array $block, string $lang): void
    {
        $label = self::t($block, $lang);
        $href = $block['url'] ?? $block['href'] ?? '#';
        if (!$label)
            return;

        $target = !empty($block['external']) ? ' target="_blank" rel="noopener"' : '';
        echo '<p class="nucleus-link"><a href="' . htmlspecialchars($href) . '"' . $target . '>'
            . htmlspecialchars($label) . "</a></p>\n";
    }
    private static function renderImage(array $block): void
    {
        $src = $block['src'] ?? null;
        if (!$src)
            return;

        $alt = $block['alt'] ?? '';
        $imgPath = '/public/img/content/' . htmlspecialchars($src);

        echo '<img'
            . ' class="nucleus-image"'
            . ' src="' . $imgPath . '"'
            . ' alt="' . htmlspecialchars($alt) . '"'
            . ' loading="lazy"'
            . '>' . "\n";
    }

    /**
     * Résout le texte dans la langue demandée avec fallback fr
     * Supporte { "fr": "..." } direct ou { "data": { "fr": "..." } }
     */
    private static function t(array $block, string $lang): string
    {
        $src = isset($block['data']) ? $block['data'] : $block;
        return $src[$lang] ?? $src['fr'] ?? $src['en'] ?? '';
    }
}