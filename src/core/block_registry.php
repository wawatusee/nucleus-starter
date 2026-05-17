<?php
/**
 * BlockRegistry - Définition et validation des types de blocs
 * 
 * Chaque type de bloc déclare :
 * - label : Nom affiché dans l'admin
 * - fields : Champs spécifiques au type (hors 'data' multilingue)
 * - dataType : Type de la donnée multilingue ('string', 'array')
 */
class BlockRegistry
{
    private static array $types = [
        'title' => [
            'label' => 'Titre',
            'fields' => [
                'level' => [
                    'type' => 'int',
                    'default' => 2,
                    'min' => 1,
                    'max' => 6
                ]
            ],
            'dataType' => 'string'
        ],
        'text' => [
            'label' => 'Paragraphe',
            'fields' => [],
            'dataType' => 'string'
        ],
        'list' => [
            'label' => 'Liste à puces',
            'fields' => [],
            'dataType' => 'array'
        ],
        'link' => [
            'label' => 'Lien / Bouton',
            'fields' => [
                'url' => [
                    'type' => 'url',
                    'required' => true
                ]
            ],
            'dataType' => 'string'
        ],
        'image' => [
            'label' => 'Image',
            'fields' => [
                'src' => [
                    'type' => 'string',
                    'required' => true
                ],
                'alt' => [
                    'type' => 'string',
                    'required' => false
                ]
            ],
            'dataType' => null   // pas de champ data multilingue
        ]
    ];

    /**
     * Vérifie si un type de bloc existe
     */
    public static function exists(string $type): bool
    {
        return isset(self::$types[$type]);
    }

    /**
     * Retourne la définition d'un type
     */
    public static function get(string $type): ?array
    {
        return self::$types[$type] ?? null;
    }

    /**
     * Retourne tous les types disponibles
     */
    public static function all(): array
    {
        return self::$types;
    }

    /**
     * Retourne la liste des types pour un <select>
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::$types as $key => $def) {
            $options[$key] = $def['label'];
        }
        return $options;
    }

    /**
     * Valide un bloc complet
     * 
     * @param array $block Le bloc à valider
     * @param array $langs Les langues supportées ['fr', 'en']
     * @return array ['valid' => bool, 'errors' => [...]]
     */
    public static function validate(array $block, array $langs): array
    {
        $errors = [];

        // 1. Type obligatoire et connu
        if (!isset($block['type'])) {
            return ['valid' => false, 'errors' => ['Type de bloc manquant']];
        }

        if (!self::exists($block['type'])) {
            return ['valid' => false, 'errors' => ["Type inconnu : {$block['type']}"]];
        }

        $def = self::$types[$block['type']];

        // 2. Validation des champs spécifiques
        foreach ($def['fields'] as $fieldName => $fieldDef) {
            $value = $block[$fieldName] ?? null;

            // Champ requis
            if (($fieldDef['required'] ?? false) && empty($value)) {
                $errors[] = "Champ '{$fieldName}' requis pour le type '{$block['type']}'";
                continue;
            }

            // Validation par type
            if ($value !== null) {
                switch ($fieldDef['type']) {
                    case 'int':
                        if (!is_numeric($value)) {
                            $errors[] = "'{$fieldName}' doit être un nombre";
                        } else {
                            $intVal = (int) $value;
                            if (isset($fieldDef['min']) && $intVal < $fieldDef['min']) {
                                $errors[] = "'{$fieldName}' minimum : {$fieldDef['min']}";
                            }
                            if (isset($fieldDef['max']) && $intVal > $fieldDef['max']) {
                                $errors[] = "'{$fieldName}' maximum : {$fieldDef['max']}";
                            }
                        }
                        break;

                    case 'url':
                        if (!filter_var($value, FILTER_VALIDATE_URL) && $value !== '#') {
                            $errors[] = "'{$fieldName}' doit être une URL valide";
                        }
                        break;
                }
            }
        }

        // 3. Validation du champ 'data' multilingue — seulement si dataType défini
        if ($def['dataType'] !== null) {
            if (!isset($block['data']) || !is_array($block['data'])) {
                $errors[] = "Champ 'data' manquant ou invalide";
            } else {
                foreach ($langs as $lang) {
                    if (!array_key_exists($lang, $block['data'])) {
                        $errors[] = "Langue '{$lang}' manquante dans 'data'";
                    } else {
                        $langValue = $block['data'][$lang];

                        if ($def['dataType'] === 'array' && !is_array($langValue)) {
                            $errors[] = "'{$lang}' doit être un tableau pour le type '{$block['type']}'";
                        }
                        if ($def['dataType'] === 'string' && !is_string($langValue)) {
                            $errors[] = "'{$lang}' doit être une chaîne pour le type '{$block['type']}'";
                        }
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Normalise un bloc (applique les valeurs par défaut)
     */
    public static function normalize(array $block, array $langs): array
    {
        if (!self::exists($block['type'])) {
            return $block;
        }

        $def = self::$types[$block['type']];

        // Appliquer les valeurs par défaut
        foreach ($def['fields'] as $fieldName => $fieldDef) {
            if (!isset($block[$fieldName]) && isset($fieldDef['default'])) {
                $block[$fieldName] = $fieldDef['default'];
            }
        }

        // S'assurer que toutes les langues sont présentes — seulement si dataType défini
        if ($def['dataType'] !== null) {
            if (!isset($block['data'])) {
                $block['data'] = [];
            }
            $emptyValue = $def['dataType'] === 'array' ? [] : '';
            foreach ($langs as $lang) {
                if (!isset($block['data'][$lang])) {
                    $block['data'][$lang] = $emptyValue;
                }
            }
        }

        return $block;
    }
}