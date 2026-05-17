<?php
/**
 * PageModel - CRUD pour les pages Nucleus
 *
 * Une page = { type, meta, layout[] }
 * Le layout référence des composants existants (article_ref, gallery_ref)
 * Pas de blocs directs : pas de validation BlockRegistry
 */

require_once __DIR__ . '/../utils/json_handler.php';

class PageModel
{
    private string $storageDir;

    // Types de références autorisés dans le layout
    private const ALLOWED_REF_TYPES = ['article_ref', 'gallery_ref'];

    /**
     * @param string $storageDir Répertoire de stockage (JSON_PAGES_DIR)
     */
    public function __construct(string $storageDir)
    {
        $this->storageDir = rtrim($storageDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Génère un ID slug à partir d'un titre
     */
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

        return $slug ?: 'page-' . time();
    }

    /**
     * Crée une nouvelle page vide
     */
    public function createEmpty(string $title): array
    {
        $id = $this->generateId($title);

        return [
            'type' => 'page',
            'meta' => [
                'id'      => $id,
                'created' => date('Y-m-d'),
                'updated' => date('Y-m-d'),
                'status'  => 'draft',
                'author'  => $_SESSION['user'] ?? 'admin'
            ],
            'layout' => []
        ];
    }

    /**
     * Charge une page par son ID ou nom de fichier
     */
    public function load(string $identifier): array
    {
        $filename = $this->resolveFilename($identifier);
        $path = $this->storageDir . $filename;

        return JsonHandler::load($path);
    }

    /**
     * Sauvegarde une page avec validation du layout
     *
     * @return array ['success' => bool, 'errors' => [...], 'filename' => string|null]
     */
    public function save(array $data): array
    {
        $errors = [];

        // 1. Validation structure de base
        if (empty($data['type']) || $data['type'] !== 'page') {
            $errors[] = "Type invalide : doit être 'page'";
        }

        if (empty($data['meta']['id'])) {
            $errors[] = "ID de page manquant";
        }

        if (!isset($data['layout']) || !is_array($data['layout'])) {
            $errors[] = "Layout manquant ou invalide";
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'filename' => null];
        }

        // 2. Validation de chaque entrée du layout
        foreach ($data['layout'] as $index => $entry) {
            $num = $index + 1;

            if (empty($entry['type'])) {
                $errors[] = "Entrée #{$num} : type manquant";
                continue;
            }

            if (!in_array($entry['type'], self::ALLOWED_REF_TYPES, true)) {
                $allowed = implode(', ', self::ALLOWED_REF_TYPES);
                $errors[] = "Entrée #{$num} : type '{$entry['type']}' invalide (autorisés : {$allowed})";
                continue;
            }

            if ($entry['type'] === 'article_ref' && empty($entry['filename'])) {
                $errors[] = "Entrée #{$num} (article_ref) : filename manquant";
            }

            if ($entry['type'] === 'gallery_ref' && empty($entry['folder'])) {
                $errors[] = "Entrée #{$num} (gallery_ref) : folder manquant";
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'filename' => null];
        }

        // 3. Mise à jour métadonnées
        $data['meta']['updated'] = date('Y-m-d');

        // 4. Sauvegarde atomique
        $filename = $data['meta']['id'] . '.json';
        $path = $this->storageDir . $filename;

        try {
            JsonHandler::save($path, $data);
            return ['success' => true, 'errors' => [], 'filename' => $filename];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()], 'filename' => null];
        }
    }

    /**
     * Supprime une page
     */
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

    /**
     * Liste toutes les pages du répertoire
     *
     * @param bool $withMeta Si true, charge les métadonnées de chaque fichier
     */
    public function listAll(bool $withMeta = false): array
    {
        $files = JsonHandler::listFiles($this->storageDir);

        if (!$withMeta) {
            return $files;
        }

        $pages = [];

        foreach ($files as $filename) {
            try {
                $data = JsonHandler::load($this->storageDir . $filename);
                $pages[] = [
                    'filename'     => $filename,
                    'id'           => $data['meta']['id'] ?? $filename,
                    'status'       => $data['meta']['status'] ?? 'unknown',
                    'updated'      => $data['meta']['updated'] ?? null,
                    'layoutCount'  => count($data['layout'] ?? [])
                ];
            } catch (Exception $e) {
                $pages[] = [
                    'filename' => $filename,
                    'id'       => $filename,
                    'status'   => 'error',
                    'error'    => $e->getMessage()
                ];
            }
        }

        return $pages;
    }

    /**
     * Vérifie si une page existe
     */
    public function exists(string $identifier): bool
    {
        $filename = $this->resolveFilename($identifier);
        return JsonHandler::exists($this->storageDir . $filename);
    }

    /**
     * Résout un identifiant en nom de fichier
     */
    private function resolveFilename(string $identifier): string
    {
        return str_ends_with($identifier, '.json') ? $identifier : $identifier . '.json';
    }
}