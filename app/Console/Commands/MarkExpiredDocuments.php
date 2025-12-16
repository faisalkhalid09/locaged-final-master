<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MarkExpiredDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:mark-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark documents as expired when they pass their expiration date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired documents...');

        // Find documents that have expired but not yet marked
        $expiredDocuments = \App\Models\Document::withoutGlobalScopes()
            ->where('is_expired', false)
            ->whereNotNull('expire_at')
            ->whereDate('expire_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($expiredDocuments as $document) {
            $document->is_expired = true;
            $document->save();
            
            // Log the expiration
            try {
                $document->logAction('expired');
            } catch (\Throwable $e) {
                $this->warn("Failed to log expiration for document {$document->id}: {$e->getMessage()}");
            }
            
            $count++;
        }

        $this->info("Marked {$count} document(s) as expired.");
        
        return Command::SUCCESS;
    }
}
