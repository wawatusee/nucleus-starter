<?php

class JsonLoader
{
    public static function load(string $path): array
    {
        if (!file_exists($path)) {
            throw new Exception("JSON introuvable : $path");
        }

        $data = json_decode(file_get_contents($path), true);

        if ($data === null) {
            throw new Exception("JSON invalide : $path");
        }

        return $data;
    }
}
