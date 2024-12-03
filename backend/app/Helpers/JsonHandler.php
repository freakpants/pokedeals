<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class JsonHandler
{
    public static function loadJson(string $filePath): array
    {
        return json_decode(file_get_contents($filePath), true);
    }

    public static function saveJson(string $directory, string $filePath, array $data): array
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        file_put_contents($filePath, json_encode($data));
        return $data;
    }
}
