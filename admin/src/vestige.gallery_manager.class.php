<?php

class GalleryManager
{
    private $baseDir;
    private $indexPath;

    public function __construct($baseDir)
    {
        $this->baseDir = rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR;
        $this->indexPath = $this->baseDir . 'galleries_index.json';
    }

    // --- MÉTHODES ORIGINALES (AMÉLIORÉES) ---

    public function createGallery($name)
    {
        $folderName = $this->formatGalleryName($name);
        $galleryDir = $this->baseDir . $folderName;

        if (!is_dir($galleryDir)) {
            mkdir($galleryDir . '/original', 0777, true);
            mkdir($galleryDir . '/thumbs', 0777, true);
            $this->refreshIndex(); // Mise à jour auto après création
        } else {
            throw new Exception("La galerie existe déjà.");
        }
    }

    public function renameGallery($oldName, $newName)
    {
        $oldDir = $this->baseDir . $this->formatGalleryName($oldName);
        $newDir = $this->baseDir . $this->formatGalleryName($newName);

        if (is_dir($oldDir) && !is_dir($newDir)) {
            rename($oldDir, $newDir);
            $this->refreshIndex(); // Mise à jour auto après renommage
        } else {
            throw new Exception("Impossible de renommer la galerie.");
        }
    }

    public function deleteImage($galleryName, $filename)
    {
        // 1. Sécurisation des noms pour éviter les injections de chemin
        $galleryName = basename($galleryName);
        $filename = basename($filename);

        // 2. Construction du chemin vers LA galerie spécifique
        // On part de baseDir (racine) + nom de la galerie
        $galleryPath = $this->baseDir . $galleryName . DIRECTORY_SEPARATOR;

        $original = $galleryPath . 'original' . DIRECTORY_SEPARATOR . $filename;
        $thumb = $galleryPath . 'thumbs' . DIRECTORY_SEPARATOR . $filename;

        // 3. Suppression physique
        if (file_exists($original))
            unlink($original);
        if (file_exists($thumb))
            unlink($thumb);

        // 4. Mise à jour de l'index
        return $this->refreshIndex();
    }

    // --- NOUVELLE MÉTHODE : LE PONT VERS LE BUILDER ---

    public function refreshIndex()
    {
        $galleries = [];
        $folders = array_filter(glob($this->baseDir . '*'), 'is_dir');

        foreach ($folders as $folder) {
            $name = basename($folder);
            $thumbsDir = $folder . DIRECTORY_SEPARATOR . 'thumbs';
            $images = [];

            if (is_dir($thumbsDir)) {
                // Le secret : on cherche toutes les extensions possibles en une seule fois
                // Le GLOB_BRACE permet de chercher {jpg,jpeg,png,gif} sans distinction
                $files = glob($thumbsDir . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,JPG,JPEG,png,PNG,gif,GIF}', GLOB_BRACE);

                if ($files) {
                    foreach ($files as $file) {
                        $images[] = [
                            'name' => basename($file),
                            'url' => 'img/content/galleries/' . $name . '/thumbs/' . basename($file)
                        ];
                    }
                }
            }

            $galleries[] = [
                'id' => $name,
                'name' => str_replace(['-', '_'], ' ', $name),
                'images' => $images
            ];
        }

        return file_put_contents($this->indexPath, json_encode($galleries, JSON_PRETTY_PRINT));
    }

    // --- UTILITAIRES ---

    private function formatGalleryName($name)
    {
        // On garde ta logique strtoupper en ajoutant une sécurité sur les espaces 
        return strtoupper(str_replace(' ', '-', trim($name)));
    }

    /* private function deleteDirectory($dir)
     {
         $files = array_diff(scandir($dir), array('.', '..'));
         foreach ($files as $file) {
             (is_dir("$dir/$file")) ? $this->deleteDirectory("$dir/$file") : unlink("$dir/$file");
         }
         return rmdir($dir);
     }*/
    public function deleteGallery($galleryName)
    {
        $galleryName = basename($galleryName);
        $galleryPath = $this->baseDir . $galleryName . DIRECTORY_SEPARATOR;

        if (!is_dir($galleryPath))
            return false;

        // 1. Liste des sous-dossiers à vider
        $subfolders = ['original', 'thumbs'];

        foreach ($subfolders as $sub) {
            $path = $galleryPath . $sub . DIRECTORY_SEPARATOR;
            if (is_dir($path)) {
                // On vide tous les fichiers du sous-dossier
                $files = glob($path . '*');
                foreach ($files as $file) {
                    if (is_file($file))
                        unlink($file);
                }
                // On supprime le sous-dossier maintenant vide
                rmdir($path);
            }
        }

        // 2. Supprimer les fichiers qui traîneraient à la racine de la galerie (ex: index.json local)
        $remainingFiles = glob($galleryPath . '*');
        foreach ($remainingFiles as $file) {
            if (is_file($file))
                unlink($file);
        }

        // 3. Enfin, on supprime le dossier de la galerie lui-même
        $success = rmdir($galleryPath);

        // 4. On met à jour l'index global pour faire disparaître la galerie de la liste
        if ($success) {
            $this->refreshIndex();
        }

        return $success;
    }
    private function generateWebVersion($inputPath, $outputPath, $type)
    {
        // 1. Chargement précis (selon ton ancienne classe)
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($inputPath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($inputPath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($inputPath);
                break;
            default:
                return false;
        }

        $w = imagesx($source);
        $h = imagesy($source);
        $aspectRatio = $w / $h;

        // 2. TON ANCIENNE LOGIQUE DE RATIO (Le secret du Masonry)
        if ($aspectRatio > 1) { // Paysage
            $newW = 1280;
            $newH = round(1280 / $aspectRatio);
        } else { // Portrait
            $newH = 1280;
            $newW = round(1280 * $aspectRatio);
        }

        // 3. Création de l'image de travail
        $target = imagecreatetruecolor($newW, $newH);

        // GESTION DE LA TRANSPARENCE (Indispensable pour tes PNG)
        imagealphablending($target, false);
        imagesavealpha($target, true);
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $white);

        imagecopyresampled($target, $source, 0, 0, 0, 0, $newW, $newH, $w, $h);

        // 4. SAUVEGARDE EN JPEG (Pour la légèreté sur le site public)
        // On force l'extension .jpg pour l'indexation facile
        imagejpeg($target, $outputPath, 85);

        imagedestroy($source);
        imagedestroy($target);
        return true;
    }
}