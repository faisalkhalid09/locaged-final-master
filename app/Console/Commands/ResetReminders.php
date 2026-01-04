<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;

class ResetReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:reset-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset first_reminder_sent_at for all documents to allow re-testing reminders.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = Document::whereNotNull('first_reminder_sent_at')->count();
        
        if ($count === 0) {
            $this->info("No documents found with reminders sent.");
            return;
        }

        if ($this->confirm("Found $count documents with reminders already sent. Reset them to NULL?")) {
            Document::whereNotNull('first_reminder_sent_at')->update(['first_reminder_sent_at' => null]);
            $this->info("Reset complete. You can now run 'php artisan schedule:test' again.");
        }
    }
}
