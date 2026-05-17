<?php
/**
 * ImageUploader — Upload et traitement d'images de contenu
 * Nucleus CMS
 *
 * Entrée  : fichier uploadé + nom de répertoire (ex: 'home')
 * Sortie  : ['base' => 'home/photo', 'ext' => 'jpg']
 *
 * Structure produite :
 *   public/img/content/{dir}/photo.jpg        ← grand format (max 1280px)
 *   public/img/content/{dir}/thumbs/photo.jpg ← miniature (400px)
 *
 * Prérequis : les dossiers {dir}/ et {dir}/thumbs/ doivent exister.
 */
class ImageUploader
{
    private const MAX_WIDTH_FULL  = 1280;
    private const MAX_WIDTH_THUMB = 400;
    private const QUALITY_JPG     = 85;

    private string $baseDir;
    private string $dir;

    /**
     * @param string $contentDir  Chemin absolu vers public/img/content/
     * @param string $dir         Nom du répertoire cible (ex: 'home')
     */
    public function __construct(string $contentDir, string $dir)
    {
        $this->baseDir = rtrim($contentDir, '/\\') . DIRECTORY_SEPARATOR;
        $this->dir     = trim($dir, '/\\');
    }

    /**
     * Traite et enregistre le fichier uploadé
     *
     * @param array $file  Entrée $_FILES['...']
     * @return array ['base' => 'home/photo', 'ext' => 'jpg']
     * @throws Exception
     */
    public function upload(array $file): array
    {
        // 1. Validation
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Erreur upload : code " . ($file['error'] ?? 'inconnu'));
        }

        $fileInfo = getimagesize($file['tmp_name']);
        if (!$fileInfo) {
            throw new Exception("Le fichier n'est pas une image valide.");
        }

        $imageType = $fileInfo[2];
        if (!in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new Exception("Format non supporté — JPEG, PNG ou WebP uniquement.");
        }

        // 2. Nom de fichier
        $slug     = $this->slugify(pathinfo($file['name'], PATHINFO_FILENAME));
        $filename = $slug . '.jpg';

        // 3. Chemins cibles
        $pathFull  = $this->baseDir . $this->dir . DIRECTORY_SEPARATOR . $filename;
        $pathThumb = $this->baseDir . $this->dir . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR . $filename;

        // 4. Traitement
        $this->processResize($file['tmp_name'], $pathFull,  self::MAX_WIDTH_FULL,  $imageType);
        $this->processResize($file['tmp_name'], $pathThumb, self::MAX_WIDTH_THUMB, $imageType);

        // 5. Retourne ce que le JSON attend
        return [
            'base' => $this->dir . '/' . $slug,
            'ext'  => 'jpg'
        ];
    }

    /**
     * Redimensionne et convertit en JPG
     */
    private function processResize(string $input, string $output, int $maxWidth, int $imageType): void
    {
        $img = match ($imageType) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($input),
            IMAGETYPE_PNG  => imagecreatefrompng($input),
            IMAGETYPE_WEBP => imagecreatefromwebp($input),
            default        => throw new Exception("Type d'image non supporté.")
        };

        $origW = imagesx($img);
        $origH = imagesy($img);
        $newW  = min($maxWidth, $origW);
        $newH  = (int) round($newW * $origH / $origW);

        $canvas = imagecreatetruecolor($newW, $newH);

        // Fond blanc — gère la transparence PNG/WebP
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        imagecopyresampled($canvas, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagejpeg($canvas, $output, self::QUALITY_JPG);

        imagedestroy($img);
        imagedestroy($canvas);
    }

    /**
     * Produit un nom de fichier propre
     */
    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[àáâãäå]/u', 'a', $text);
        $text = preg_replace('/[èéêë]/u',   'e', $text);
        $text = preg_replace('/[ìíîï]/u',   'i', $text);
        $text = preg_replace('/[òóôõö]/u',  'o', $text);
        $text = preg_replace('/[ùúûü]/u',   'u', $text);
        $text = preg_replace('/[ç]/u',      'c', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}
