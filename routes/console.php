<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// AUDIT FIX #4: Automated backups - daily at 02:00
// Run: composer require spatie/laravel-backup on server first
Schedule::command('backup:run')->dailyAt('02:00');
Schedule::command('backup:clean')->dailyAt('03:00');
Schedule::command('backup:monitor')->dailyAt('03:30');

// Mark documents as expired based on expiration date (new is_expired flag)
Schedule::command('documents:mark-expired')->dailyAt('00:30');

Schedule::call(function () {
    $expiredDocuments =  \App\Models\Document::where('expire_at', '<=', now())
        ->get();

    foreach ($expiredDocuments as $document) {
        // Keep the existing status but record an "expired" audit/log entry
        $document->logAction('expired');
    }

})->dailyAt('01:00')
    ->description('Log expired documents');

// Automatically create destruction requests for expired documents
Schedule::command('documents:check-expired')->dailyAt('01:10');

// Automatic approval reminders for pending documents
Schedule::call(function () {
    $now = now();

    $oneWeekAgo = $now->clone()->subWeek();
    $oneMonthAgo = $now->clone()->subMonth();

    // 1-week reminders
    $weekDocuments = \App\Models\Document::where('status', 'pending')
        ->whereNull('first_reminder_sent_at')
        ->where('created_at', '<=', $oneWeekAgo)
        ->with(['latestVersion', 'createdBy'])
        ->get();

    foreach ($weekDocuments as $document) {
        if (! $document->createdBy) {
            continue;
        }

        $notificationService = new \App\Services\NotificationService(
            $document->title,
            $document->createdBy,
            $document->id,
            optional($document->latestVersion)->id
        );

        $notificationService->notifyBasedOnAction('pending_approval_1w');

        $document->forceFill([
            'first_reminder_sent_at' => $now,
        ])->save();
    }

    // 1-month reminders
    $monthDocuments = \App\Models\Document::where('status', 'pending')
        ->whereNull('second_reminder_sent_at')
        ->where('created_at', '<=', $oneMonthAgo)
        ->with(['latestVersion', 'createdBy'])
        ->get();

    foreach ($monthDocuments as $document) {
        if (! $document->createdBy) {
            continue;
        }

        $notificationService = new \App\Services\NotificationService(
            $document->title,
            $document->createdBy,
            $document->id,
            optional($document->latestVersion)->id
        );

        $notificationService->notifyBasedOnAction('pending_approval_1m');

        $document->forceFill([
            'second_reminder_sent_at' => $now,
        ])->save();
    }

})->dailyAt('02:00')
->description('Send automated approval reminders');

// -----------------------------------------------------------------------------
// Utility command: force-expire a document for testing destruction/postpone
// Usage examples:
//   php artisan documents:force-expire 123       (by ID)
//   php artisan documents:force-expire "My Doc"  (by exact title)
// -----------------------------------------------------------------------------
Artisan::command('documents:force-expire {identifier}', function (string $identifier) {
    $query = \App\Models\Document::query();

    if (is_numeric($identifier)) {
        $query->where('id', (int) $identifier);
    } else {
        $query->where('title', $identifier);
    }

    $documents = $query->get();

    if ($documents->isEmpty()) {
        $this->error('No matching documents found for identifier: ' . $identifier);
        return 1;
    }

    foreach ($documents as $document) {
        $document->expire_at = now()->subDay();
        $document->save();
        $document->logAction('expired');

        $this->info("Marked document #{$document->id} ({$document->title}) as expired.");
    }

    return 0;
})->describe('Force a document to expired state for testing.');

// Utility command: expire all documents for testing destruction flow
Artisan::command('documents:expire-all', function () {
    $now = now()->subDay();

    $count = \App\Models\Document::query()
        ->update([
            'expire_at' => $now,
        ]);

    $this->info("Expired {$count} documents.");
})->describe('Mark all documents as expired for testing destruction flow.');

// Queue destruction requests for all expired documents
Artisan::command('documents:queue-destructions', function () {
    $expiredDocuments = \App\Models\Document::where('expire_at', '<=', now())
        ->whereNotIn('status', ['destroyed', 'archived'])
        ->get();

    if ($expiredDocuments->isEmpty()) {
        $this->info('No expired documents found.');
        return 0;
    }

    $this->info("Found {$expiredDocuments->count()} expired document(s).");

    $systemUser = \App\Models\User::role(['master', 'Super Administrator'])->first()
        ?? \App\Models\User::first();

    if (! $systemUser) {
        $this->error('No user found to assign as requester.');
        return 1;
    }

    $created = 0;
    $skipped = 0;

    foreach ($expiredDocuments as $document) {
        $existingRequest = \App\Models\DocumentDestructionRequest::where('document_id', $document->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->first();

        if ($existingRequest) {
            $skipped++;
            continue;
        }

        \App\Models\DocumentDestructionRequest::create([
            'document_id' => $document->id,
            'requested_by' => $systemUser->id,
            'requested_at' => now(),
            'status' => 'pending',
        ]);

        $created++;
    }

    $this->info("Created: {$created}; Skipped: {$skipped}; Total: {$expiredDocuments->count()}");

    return 0;
})->describe('Queue destruction requests for all expired documents.');
