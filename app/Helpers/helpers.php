<?php

if (!function_exists('getFileCategory')) {
    function getFileCategory(string $extension): string
    {
        $extension = strtolower($extension);

        $types = [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'tiff', 'svg'],
            'doc'   => ['doc', 'docx', 'odt', 'rtf', 'txt'],
            'pdf'   => ['pdf'],
            'excel' => ['xls', 'xlsx', 'csv'],
            'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'],
            'audio' => ['mp3', 'wav', 'flac', 'aac', 'ogg', 'm4a'],
        ];

        foreach ($types as $category => $ext) {
            if (in_array($extension, $ext)) {
                return $category;
            }
        }

        return 'other';
    }
}

if (!function_exists('getFileIcon')) {
    function getFileIcon(string $extension): string
    {
        $extension = strtolower($extension);
        
        $iconMap = [
            // Images
            'jpg' => 'fas fa-file-image text-warning',
            'jpeg' => 'fas fa-file-image text-warning',
            'png' => 'fas fa-file-image text-warning',
            'gif' => 'fas fa-file-image text-warning',
            'bmp' => 'fas fa-file-image text-warning',
            'webp' => 'fas fa-file-image text-warning',
            'tiff' => 'fas fa-file-image text-warning',
            'svg' => 'fas fa-file-image text-warning',
            
            // PDF
            'pdf' => 'fas fa-file-pdf text-danger',
            
            // Word Documents
            'doc' => 'fas fa-file-word text-primary',
            'docx' => 'fas fa-file-word text-primary',
            'odt' => 'fas fa-file-word text-primary',
            'rtf' => 'fas fa-file-word text-primary',
            'txt' => 'fas fa-file-alt text-secondary',
            
            // Excel Files
            'xls' => 'fas fa-file-excel text-success',
            'xlsx' => 'fas fa-file-excel text-success',
            'csv' => 'fas fa-file-csv text-success',
            
            // Video Files
            'mp4' => 'fas fa-file-video text-info',
            'avi' => 'fas fa-file-video text-info',
            'mov' => 'fas fa-file-video text-info',
            'wmv' => 'fas fa-file-video text-info',
            'flv' => 'fas fa-file-video text-info',
            'webm' => 'fas fa-file-video text-info',
            'mkv' => 'fas fa-file-video text-info',
            
            // Audio Files
            'mp3' => 'fas fa-file-audio text-purple',
            'wav' => 'fas fa-file-audio text-purple',
            'flac' => 'fas fa-file-audio text-purple',
            'aac' => 'fas fa-file-audio text-purple',
            'ogg' => 'fas fa-file-audio text-purple',
            'm4a' => 'fas fa-file-audio text-purple',
        ];
        
        return $iconMap[$extension] ?? 'fas fa-file text-secondary';
    }
}

if (!function_exists('ui_t')) {
    /**
     * Translate using DB overrides from ui_translations, falling back to file-based keyed translations.
     */
    function ui_t(string $key, array $replace = [], ?string $locale = null): string
    {
        static $cache = [];
        $locale = $locale ?: app()->getLocale();

        if (!isset($cache[$locale])) {
            $map = [];
            try {
                $records = \App\Models\UiTranslation::query()->get(['key', 'en_text', 'ar_text', 'fr_text']);
                foreach ($records as $r) {
                    $map[$r->key] = [
                        'en' => $r->en_text,
                        'ar' => $r->ar_text,
                        'fr' => $r->fr_text,
                    ];
                }
            } catch (\Throwable $e) {
                $map = [];
            }
            $cache[$locale] = $map;
        }

        $db = $cache[$locale][$key] ?? null;
        $value = null;
        if ($db) {
            $value = $db[$locale] ?? null;
        }

        $text = $value ?: __($key, $replace, $locale);

        // If translation result is an array (e.g., key points to array in translation file), fallback to key
        if (is_array($text)) {
            $text = $key;
        }

        // Replace named placeholders in DB-provided values if present
        if ($value && !empty($replace)) {
            foreach ($replace as $k => $v) {
                $text = str_replace(':' . $k, (string) $v, $text); 
            }
        }

        return (string) $text;
    }
}
