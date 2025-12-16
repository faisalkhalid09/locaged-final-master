\u003c?php

namespace App\\Console\\Commands;

use App\\Models\\Document;
use App\\Models\\DocumentDestructionRequest;
use Illuminate\\Console\\Command;
use Illuminate\\Support\\Facades\\Log;
use Illuminate\\Support\\Facades\\DB;

class CheckExpiredDocuments extends Command
{
    protected $signature = 'documents:check-expired';
    protected $description = 'Check for expired documents and create destruction requests automatically';

    public function handle()
    {
        $this->info('Checking for expired documents...');

        // Find documents that have expired but are not yet destroyed or archived
        $expiredDocuments = Document::where('expire_at', '<=', now())
            ->whereNotIn('status', ['destroyed', 'archived'])
            ->get();

        if ($expiredDocuments->isEmpty()) {
            $this->info('No expired documents found.');
            return 0;
        }

        $this->info("Found {$expiredDocuments->count()} expired document(s).");

        $created = 0;
        $skipped = 0;

        foreach ($expiredDocuments as $document) {
            // Check if a destruction request already exists for this document
            $existingRequest = DocumentDestructionRequest::where('document_id', $document->id)
                ->whereIn('status', ['pending', 'accepted'])
                ->first();

            if ($existingRequest) {
                $this->line("  Skipped: {$document->title} (destruction request already exists)");
                $skipped++;
                continue;
            }

            // Find a system user (first admin) to assign as requester
            $systemUser = \App\Models\User::role(['master', 'Super Administrator'])->first() ?? \App\Models\User::first();
            
            if (!$systemUser) {
                $this->error("No user found to assign as requester.");
                return 1;
            }

            // Create a new destruction request
            try {
                DB::beginTransaction();

                DocumentDestructionRequest::create([
                    'document_id' => $document->id,
                    'requested_by' => $systemUser->id,
                    'requested_at' => now(),
                    'status' => 'pending',
                ]);

                // Record an "expired" audit/log entry without changing the status enum
                $document->logAction('expired');

                DB::commit();

                $this->line("  ✓ Created destruction request & marked expired: {$document->title}");
                $created++;

                Log::info('Automatic destruction request created and document expired', [
                    'document_id' => $document->id,
                    'document_title' => $document->title,
                    'expired_at' => $document->expire_at,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("  ✗ Failed to process: {$document->title}");
                $this->error("    Error: {$e->getMessage()}");
                
                Log::error('Failed to process automatic destruction', [
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  Created: {$created}");
        $this->info("  Skipped: {$skipped}");
        $this->info("  Total: {$expiredDocuments->count()}");

        // Notify admins if any destruction requests were created
        if ($created > 0) {
            $this->info("\\nNotifying administrators...");
            
            try {
                $admins = \\App\\Models\\User::role(['master', 'Super Administrator', 'Admin de pole'])
                    ->get();
                
                foreach ($admins as $admin) {
                    $admin->notify(new \\App\\Notifications\\GeneralNotification(
                        'warning',
                        __('Expired Documents Require Attention'),
                        __(':count expired document(s) have been added to the destruction queue. Please review to postpone or permanently delete.', ['count' => $created]),
                        null,
                        null,
                        'expired_documents'
                    ));
                }
                
                $this->info("✓ Notified {$admins->count()} administrator(s)");
            } catch (\\Exception $e) {
                $this->warn("Failed to send notifications: {$e->getMessage()}");
            }
        }

        return 0;
    }
}
