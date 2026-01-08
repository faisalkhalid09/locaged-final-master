<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Maximum Upload Sizes
    |--------------------------------------------------------------------------
    |
    | The limits here are expressed in kilobytes (KB), matching Laravel's
    | "max" validation rule for file uploads.
    |
    */

    // Maximum size per individual uploaded file (e.g. 50 MB).
    'max_file_size_kb' => env('UPLOAD_MAX_FILE_SIZE_KB', 50 * 1024),

    // Maximum number of files that may be included in a single multi-upload batch.
    'max_batch_files' => env('UPLOAD_MAX_BATCH_FILES', 50),

    // Maximum combined size (in KB) for all files in a single multi-upload batch.
    // Set to 0 to disable the combined-size check.
    'max_batch_size_kb' => env('UPLOAD_MAX_BATCH_SIZE_KB', 500 * 1024),

    /*
    |--------------------------------------------------------------------------
    | Allowed File Extensions
    |--------------------------------------------------------------------------
    |
    | The list of file extensions that are accepted by the application
    | for document uploads.
    |
    */

    'allowed_extensions' => [
        // Documents
        'pdf',
        'doc', 'docx', 'odt', 'rtf', 'txt',

        // Presentations
        'ppt', 'pptx',

        // Spreadsheets
        'xls', 'xlsx', 'csv',

        // Images
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'tiff', 'tif', 'svg',

        // Video
        'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv',

        // Audio
        'mp3', 'wav', 'flac', 'aac', 'ogg', 'm4a',
    ],

];
