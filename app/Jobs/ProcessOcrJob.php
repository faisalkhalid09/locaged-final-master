<?php

// app/Jobs/ProcessOcrJob.php

namespace App\Jobs;

use App\Models\OcrJob;
use App\Services\OcrService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessOcrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected OcrJob $ocrJob;
    
    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 1800; // 30 minutes
    
    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    public function __construct(OcrJob $ocrJob)
    {
        $this->ocrJob = $ocrJob;
    }

    public function handle(OcrService $ocrService): void
    {

        Log::info("OCR Job started", ['ocr_job_id' => $this->ocrJob->id]);
        $this->ocrJob->update(['status' => 'processing', 'processed_at' => now()]);

        try {
            $docVersion = $this->ocrJob->documentVersion;

            if (! $docVersion) {
                throw new Exception('Document version not found.');
            }

            $path = Storage::disk('local')->path($docVersion->file_path);
            Log::info("Processing file at path", ['path' => $path]);

            if (! file_exists($path)) {
                throw new Exception('Document file not found.');
            }

            $ocrText = $ocrService->extractText($path);

            $docVersion->update(['ocr_text' => $ocrText]);

            $docVersion->searchable();

            $this->ocrJob->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            Log::info("OCR Job completed successfully", ['ocr_job_id' => $this->ocrJob->id]);

        } catch (Exception $e) {
            Log::error('OCR Job Failed', [
                'ocr_job_id' => $this->ocrJob->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Only mark as failed if this is the last attempt
            if ($this->attempts() >= $this->tries) {
                $this->ocrJob->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                ]);
            } else {
                // Don't change status on retry - let Laravel handle retries
                $this->ocrJob->update([
                    'error_message' => $e->getMessage(),
                ]);
            }
            
            throw $e; // Re-throw to trigger retry mechanism
        }
    }

}
