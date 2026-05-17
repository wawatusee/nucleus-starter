<?php
class ImageUploader
{
    private $galleryPath;

    public function __construct($galleryPath)
    {
        // On attend ici le chemin vers le dossier parent de la galerie
        $this->galleryPath = rtrim($galleryPath, '/\\') . DIRECTORY_SEPARATOR;
    }

    public function upload($file)
    {
        if (!isset($file) || $file['error'] != 0) {
            throw new Exception("Erreur upload: " . $file['error']);
        }

        $fileInfo = getimagesize($file['tmp_name']);
        if (!$fileInfo)
            throw new Exception("Fichier non image.");

        $extension = image_type_to_extension($imageType = $fileInfo[2]);
        $cleanName = $this->slugify(pathinfo($file['name'], PATHINFO_FILENAME)) . $extension;

        $targetOriginal = $this->galleryPath . 'original' . DIRECTORY_SEPARATOR . $cleanName;
        $targetThumb = $this->galleryPath . 'thumbs' . DIRECTORY_SEPARATOR . $cleanName;

        if (move_uploaded_file($file['tmp_name'], $targetOriginal)) {
            // 1. Optimisation de l'original (max 1280px)
            $this->processResize($targetOriginal, $targetOriginal, 1280);
            // 2. Création auto de la miniature (400px)
            $this->processResize($targetOriginal, $targetThumb, 400);

            return $cleanName;
        }
        return false;
    }

    private function processResize($input, $output, $width)
    {
        $info = getimagesize($input);
        $type = $info[2];

        switch ($type) {
            case IMAGETYPE_JPEG:
                $img = imagecreatefromjpeg($input);
                break;
            case IMAGETYPE_PNG:
                $img = imagecreatefrompng($input);
                break;
            case IMAGETYPE_GIF:
                $img = imagecreatefromgif($input);
                break;
            default:
                return;
        }

        $origW = imagesx($img);
        $origH = imagesy($img);
        $ratio = $origW / $origH;
        $newW = min($width, $origW);
        $newH = round($newW / $ratio);

        $tmp = imagecreatetruecolor($newW, $newH);

        // Gestion de la transparence pour PNG/GIF
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagealphablending($tmp, false);
            imagesavealpha($tmp, true);
        }

        imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        // On définit l'extension de sortie de manière unique
        $output = preg_replace('/\.(png|gif|jpeg|jpg)$/i', '.jpg', $output);

        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($tmp, $output, 85);
                break;
            case IMAGETYPE_PNG:
                // Créer un fond blanc pour la transparence du PNG
                $background = imagecreatetruecolor(imagesx($tmp), imagesy($tmp));
                $white = imagecolorallocate($background, 255, 255, 255);
                imagefill($background, 0, 0, $white);
                imagecopyresampled($background, $tmp, 0, 0, 0, 0, imagesx($tmp), imagesy($tmp), imagesx($tmp), imagesy($tmp));

                imagejpeg($background, $output, 85); // On sauvegarde en JPG
                imagedestroy($background);
                break;
            case IMAGETYPE_GIF:
                imagejpeg($tmp, $output, 85); // Le GIF se convertit facilement en JPG
                break;
        }

        imagedestroy($img);
        imagedestroy($tmp);
    }

    private function slugify($text)
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text), '-'));
    }
}