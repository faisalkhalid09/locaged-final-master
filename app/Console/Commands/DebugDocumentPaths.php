<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;

class DebugDocumentPaths extends Command
{
    protected $signature = 'debug:document-paths';
    protected $description = 'List file paths for recent documents';

    public function handle()
    {
        $documents = Document::with('latestVersion')->latest()->take(10)->get();

        $this->table(
            ['ID', 'Title', 'File Path', 'Extension (Calculated)', 'Icon Class'],
            $documents->map(function ($doc) {
                $path = $doc->latestVersion?->file_path ?? 'N/A';
                $ext = $path !== 'N/A' ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : 'N/A';
                $icon = getFileIcon($ext);
                return [
                    $doc->id,
                    $doc->title,
                    $path,
                    $ext,
                    $icon
                ];
            })
        );
    }
}
