<?php
/**
 * ComponentModel - CRUD générique pour les composants Nucleus
 * 
 * Un composant = { type, meta, content[] }
 * Gère : articles, contacts, et tout futur type de composant
 */

require_once __DIR__ . '/../utils/json_handler.php';
require_once __DIR__ . '/block_registry.php';

class ComponentModel
{
    private string $storageDir;
    private array $langs;
    private string $componentType;

    /**
     * @param string $storageDir Répertoire de stockage (ex: JSON_ARTICLES_DIR)
     * @param array $langs Langues supportées ['fr', 'en']
     * @param string $componentType Type de composant ('article', 'contact', etc.)
     */
    public function __construct(string $storageDir, array $langs, string $componentType = 'article')
    {
        $this->storageDir = rtrim($storageDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->langs = $langs;
        $this->componentType = $componentType;
    }

    /**
     * Génère un ID unique à partir d'un titre
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
        
        return $slug ?: 'composant-' . time();
    }

    /**
     * Crée un nouveau composant vide
     */
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

    /**
     * Charge un composant par son ID ou nom de fichier
     */
    public function load(string $identifier): array
    {
        $filename = $this->resolveFilename($identifier);
        $path = $this->storageDir . $filename;
        
        return JsonHandler::load($path);
    }

    /**
     * Sauvegarde un composant avec validation
     * 
     * @return array ['success' => bool, 'errors' => [...], 'filename' => string]
     */
    public function save(array $data): array
    {
        $errors = [];

        // 1. Validation de la structure de base
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

        // 2. Validation et normalisation de chaque bloc
        $normalizedContent = [];
        
        foreach ($data['content'] as $index => $block) {
            // Normaliser le bloc
            $block = BlockRegistry::normalize($block, $this->langs);
            
            // Valider le bloc
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

        // 3. Mise à jour des métadonnées
        $data['content'] = $normalizedContent;
        $data['meta']['updated'] = date('Y-m-d');

        // 4. Sauvegarde
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
     * Supprime un composant
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
     * Liste tous les composants du répertoire
     * 
     * @param bool $withMeta Si true, charge les métadonnées de chaque fichier
     * @return array
     */
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

    /**
     * Vérifie si un composant existe
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
        if (substr($identifier, -5) === '.json') {
            return $identifier;
        }
        return $identifier . '.json';
    }
}