<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;

class BackfillDocumentCategories extends Command
{
    protected $signature = 'documents:backfill-categories';
    protected $description = 'Backfill category_id for documents based on subcategory_id';

    public function handle()
    {
        $this->info('Starting backfill...');

        $documents = Document::whereNotNull('subcategory_id')
            ->whereNull('category_id')
            ->with('subcategory')
            ->cursor();

        $count = 0;
        foreach ($documents as $document) {
            if ($document->subcategory) {
                $document->category_id = $document->subcategory->category_id;
                $document->saveQuietly(); // Avoid triggering observers/events
                $count++;
            }
        }

        $this->info("Backfilled {$count} documents.");
    }
}
