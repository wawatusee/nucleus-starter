<?php
/**
 * FolderManager — Gestion de répertoires de contenu
 * Nucleus CMS
 *
 * Crée, renomme, supprime et liste des répertoires.
 * Pas de logique d'image, pas d'index JSON.
 * 
 * Usage :
 *   $fm = new FolderManager(GALLERIES_DIR);
 *   $fm->create('evenements');
 *   $fm->rename('evenements', 'events');
 *   $fm->delete('events');
 *   $fm->list();
 */
class FolderManager
{
    private string $baseDir;

    /**
     * @param string $baseDir Chemin absolu vers le répertoire parent
     */
    public function __construct(string $baseDir)
    {
        $this->baseDir = rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * Crée un répertoire
     *
     * @throws Exception Si le répertoire existe déjà
     */
    public function create(string $name): void
    {
        $path = $this->resolve($name);

        if (is_dir($path)) {
            throw new Exception("Le répertoire '{$name}' existe déjà.");
        }

        mkdir($path, 0777, true);
        mkdir($path . DIRECTORY_SEPARATOR . 'thumbs', 0777, true);
    }

    /**
     * Renomme un répertoire
     *
     * @throws Exception Si l'opération échoue
     */
    public function rename(string $oldName, string $newName): void
    {
        $oldPath = $this->resolve($oldName);
        $newPath = $this->resolve($newName);

        if (!is_dir($oldPath)) {
            throw new Exception("Le répertoire '{$oldName}' n'existe pas.");
        }

        if (is_dir($newPath)) {
            throw new Exception("Le répertoire '{$newName}' existe déjà.");
        }

        rename($oldPath, $newPath);
    }

    /**
     * Supprime un répertoire et tout son contenu
     *
     * @throws Exception Si le répertoire n'existe pas
     */
    public function delete(string $name): void
    {
        $path = $this->resolve($name);

        if (!is_dir($path)) {
            throw new Exception("Le répertoire '{$name}' n'existe pas.");
        }

        $this->deleteRecursive($path);
    }

    /**
     * Liste les répertoires du baseDir
     *
     * @return string[] Noms des répertoires
     */
    public function list(): array
    {
        $folders = array_filter(glob($this->baseDir . '*'), 'is_dir');
        return array_values(array_map('basename', $folders));
    }

    /**
     * Vérifie si un répertoire existe
     */
    public function exists(string $name): bool
    {
        return is_dir($this->resolve($name));
    }

    /**
     * Résout un nom en chemin absolu sécurisé
     */
    private function resolve(string $name): string
    {
        return $this->baseDir . basename($name);
    }

    /**
     * Supprime récursivement un répertoire
     */
    private function deleteRecursive(string $path): void
    {
        $items = array_diff(scandir($path), ['.', '..']);

        foreach ($items as $item) {
            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            is_dir($itemPath) ? $this->deleteRecursive($itemPath) : unlink($itemPath);
        }

        rmdir($path);
    }
}
