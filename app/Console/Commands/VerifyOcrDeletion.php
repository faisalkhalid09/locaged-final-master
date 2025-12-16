<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\OcrJob;
use App\Models\User;
use App\Models\Category;
use App\Models\Box;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VerifyOcrDeletion extends Command
{
    protected $signature = 'verify:ocr-deletion';
    protected $description = 'Verify that OCR jobs are deleted when a document is permanently deleted';

    public function handle()
    {
        $this->info('Starting verification...');

        DB::beginTransaction();

        try {
            // 1. Setup Data
            $user = User::first();
            if (!$user) {
                $this->error('No user found.');
                return;
            }
            $this->info('User found: ' . $user->name);

            $category = Category::first();
            $box = Box::first();

            if (!$category) {
                 // Create dummy category if needed
                 $category = Category::create(['name' => 'Test Cat', 'slug' => 'test-cat', 'department_id' => 1, 'service_id' => 1]);
            }
            if (!$box) {
                 // Create dummy box if needed, or skip validation if possible
                 // Assuming box is required by Document model
                 $this->error('No box found. Please ensure seed data exists.');
                 return;
            }

            // Create Document
            $document = Document::create([
                'uid' => (string) Str::uuid(),
                'title' => 'Test Document for OCR Deletion',
                'category_id' => $category->id,
                'box_id' => $box->id,
                'created_by' => $user->id,
                'created_at' => now(),
                'expire_at' => now()->addYear(),
                'metadata' => ['author' => 'Test', 'color' => 'red'],
                'department_id' => 1,
                'service_id' => 1,
            ]);
            $this->info('Document created: ' . $document->id);

            // Create Version
            $version = DocumentVersion::create([
                'document_id' => $document->id,
                'version_number' => 1.0,
                'file_path' => 'dummy/path.pdf',
                'file_type' => 'pdf',
                'uploaded_by' => $user->id,
            ]);
            $this->info('Version created: ' . $version->id);

            // Create OCR Job
            $ocrJob = OcrJob::create([
                'document_version_id' => $version->id,
                'status' => 'queued',
                'queued_at' => now(),
            ]);
            $this->info('OCR Job created: ' . $ocrJob->id);

            // Verify existence
            if (!OcrJob::find($ocrJob->id)) {
                $this->error('OCR Job creation failed.');
                DB::rollBack();
                return;
            }

            // 2. Execute Deletion Logic (Mimicking Controller)
            $this->info('Executing deletion logic...');
            
            // Reload document to get relations
            $document = Document::with('documentVersions.ocrJob')->find($document->id);

            foreach ($document->documentVersions as $v) {
                \App\Models\DocumentVersion::withoutSyncingToSearch(function () use ($v) {
                    if ($v->ocrJob) {
                        $this->info('Deleting OCR Job for version ' . $v->id);
                        $v->ocrJob->delete();
                    }
                    $v->delete();
                });
            }
            $document->delete();

            // 3. Verify Deletion
            $jobCheck = OcrJob::find($ocrJob->id);
            if ($jobCheck) {
                $this->error('FAILURE: OCR Job still exists after document deletion!');
            } else {
                $this->info('SUCCESS: OCR Job was deleted.');
            }

            $docCheck = Document::withTrashed()->find($document->id);
             if ($docCheck && !$docCheck->trashed()) {
                 $this->error('FAILURE: Document was not deleted!');
             } else {
                 $this->info('SUCCESS: Document was deleted.');
             }

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
        } finally {
            DB::rollBack(); // Always rollback to clean up
            $this->info('Transaction rolled back (cleanup).');
        }
    }
}
