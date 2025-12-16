<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PdfConversionService;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\Storage;

class ConvertDocumentsToPdf extends Command
{
    protected $signature = 'documents:convert-to-pdf {--force : Force reconversion even if PDF exists}';
    protected $description = 'Convert Word and Excel documents to PDF for better preview';

    private $conversionService;

    public function __construct(PdfConversionService $conversionService)
    {
        parent::__construct();
        $this->conversionService = $conversionService;
    }

    public function handle()
    {
        $this->info('Starting PDF conversion...');

        // Get all Word and Excel documents
        $documents = DocumentVersion::whereIn('file_type', ['doc', 'excel'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($documents->isEmpty()) {
            $this->info('No Word or Excel documents found.');
            return 0;
        }

        $this->info("Found {$documents->count()} documents to process.");

        $converted = 0;
        $skipped = 0;
        $failed = 0;

        $progressBar = $this->output->createProgressBar($documents->count());
        $progressBar->start();

        foreach ($documents as $doc) {
            // Check if PDF already exists
            $pathInfo = pathinfo($doc->file_path);
            $pdfPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.pdf';

            if (!$this->option('force') && Storage::disk('local')->exists($pdfPath)) {
                $skipped++;
                $progressBar->advance();
                continue;
            }

            // Attempt conversion
            $result = $this->conversionService->convertToPdf($doc->file_path);

            if ($result) {
                $converted++;
            } else {
                $failed++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Conversion complete!");
        $this->info("Converted: $converted");
        $this->info("Skipped (already exists): $skipped");
        $this->info("Failed: $failed");

        return 0;
    }
}
