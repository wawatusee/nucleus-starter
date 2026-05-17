<?php
/**
 * ConfigModel - Gestion de la configuration globale
 * Nucleus CMS - Session 2
 */

class ConfigModel
{
    private static ?array $langs = null;
    private static ?array $config = null;

    /**
     * Récupère les langues disponibles
     * @return array ['code' => 'Label', ...]
     */
    public static function getLangs(): array
    {
        if (self::$langs === null) {
            self::loadConfig();
        }
        return self::$langs;
    }

    /**
     * Récupère la langue par défaut
     * @return string Code langue (ex: 'fr')
     */
    public static function getDefaultLang(): string
    {
        $langs = self::getLangs();
        return $langs[0]['code'] ?? 'fr';
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

        $content = file_get_contents($configPath);
        $data = json_decode($content, true);

        self::$langs = [];

        if (isset($data['langs']) && is_array($data['langs'])) {
            foreach ($data['langs'] as $langItem) {
                if (isset($langItem['code'], $langItem['label'])) {
                    self::$langs[] = [
                        'code' => $langItem['code'],
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
     * Reset le cache (utile pour tests)
     */
    public static function clearCache(): void
    {
        self::$langs = null;
        self::$config = null;
    }
}