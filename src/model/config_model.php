<?php
/**
 * ConfigModel - Gestion de la configuration globale
 * Nucleus CMS
 *
 * Utilisable en contexte admin (ROOT_PATH défini par config_admin.php)
 * et en contexte public (ROOT_PATH défini par config/config.php)
 */

class ConfigModel
{
    private static ?array $langs = null;

    /**
     * Récupère les langues disponibles
     * @return array [['code' => 'fr', 'label' => 'Français'], ...]
     */
    public static function getLangs(): array
    {
        if (self::$langs === null) {
            self::loadConfig();
        }
        return self::$langs;
    }

    /**
     * Récupère la langue par défaut (première de la liste)
     * @return string Code langue (ex: 'fr')
     */
    public static function getDefaultLang(): string
    {
        $langs = self::getLangs();
        return $langs[0]['code'] ?? 'fr';
    }

    /**
     * Retourne le titre du site
     * config.json : "titleWebsite": ["mon-", "site", ".fr"]
     */
    public static function getTitle(): string
    {
        $configPath = ROOT_PATH . 'json/config.json';

        if (!file_exists($configPath)) {
            return 'Site';
        }

        $data = json_decode(file_get_contents($configPath), true);
        $parts = $data['titleWebsite'] ?? ['Site'];
        return is_array($parts) ? implode('', $parts) : (string) $parts;
    }

    /**
     * Charge la configuration depuis le JSON
     */
    private static function loadConfig(): void
    {
        $configPath = ROOT_PATH . 'json/config.json';

        if (!file_exists($configPath)) {
            self::$langs = [['code' => 'fr', 'label' => 'Français']];
            return;
        }

        $data = json_decode(file_get_contents($configPath), true);

        self::$langs = [];

        if (isset($data['langs']) && is_array($data['langs'])) {
            foreach ($data['langs'] as $langItem) {
                if (isset($langItem['code'], $langItem['label'])) {
                    self::$langs[] = [
                        'code'  => $langItem['code'],
                        'label' => $langItem['label']
                    ];
                }
            }
        }

        if (empty(self::$langs)) {
            self::$langs = [['code' => 'fr', 'label' => 'Français']];
        }
    }

    /**
     * Reset le cache (utile pour les tests)
     */
    public static function clearCache(): void
    {
        self::$langs = null;
    }
}
