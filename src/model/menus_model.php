<?php
/**
 * MenusModel
 * Charge un fichier JSON de menus et expose son contenu par type.
 */
class MenusModel
{
    private $menus;

    public function __construct(string $srcJson)
    {
        if (!file_exists($srcJson)) {
            throw new RuntimeException("MenusModel : fichier introuvable — $srcJson");
        }

        $raw = file_get_contents($srcJson);
        $decoded = json_decode($raw);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("MenusModel : JSON invalide — " . json_last_error_msg());
        }

        $this->menus = $decoded;
    }

    /**
     * @param string $menuType ex: "Main_menu", "RS_menu"
     * @return array|null
     */
    public function getMenu(string $menuType): ?array
    {
        if (!isset($this->menus->$menuType)) {
            return null;
        }
        return $this->menus->$menuType;
    }
}