<?php
/**
 * JsonHandler - Gestionnaire centralisé des fichiers JSON
 * 
 * Responsabilités :
 * - Lecture sécurisée avec gestion d'erreurs
 * - Écriture atomique (évite la corruption)
 * - Suppression avec vérification
 * - Listage des fichiers d'un répertoire
 */
class JsonHandler
{
    /**
     * Charge un fichier JSON et retourne un tableau
     * 
     * @param string $path Chemin absolu du fichier
     * @return array Données décodées
     * @throws Exception Si fichier inexistant ou JSON invalide
     */
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

    /**
     * Sauvegarde des données dans un fichier JSON
     * Écriture atomique : écrit d'abord dans un fichier temporaire
     * 
     * @param string $path Chemin absolu du fichier
     * @param array $data Données à sauvegarder
     * @return bool Succès de l'opération
     * @throws Exception Si écriture impossible
     */
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

        // Écriture atomique
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

    /**
     * Supprime un fichier JSON
     * 
     * @param string $path Chemin absolu du fichier
     * @return bool Succès de l'opération
     * @throws Exception Si suppression impossible
     */
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

    /**
     * Liste les fichiers JSON d'un répertoire
     * 
     * @param string $dir Chemin du répertoire
     * @return array Liste des noms de fichiers (sans chemin)
     */
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

    /**
     * Vérifie si un fichier existe
     * 
     * @param string $path Chemin absolu
     * @return bool
     */
    public static function exists(string $path): bool
    {
        return file_exists($path) && is_file($path);
    }
}