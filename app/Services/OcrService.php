<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OcrService
{
    // Whitelist supported extensions (images, PDFs, and common Office/text formats)
    const SUPPORTED_EXTENSIONS = [
        'pdf',
        'jpg', 'jpeg', 'png', 'tiff', 'tif', 'bmp', 'webp',
        'docx',
        'xls', 'xlsx', 'csv',
        'txt',
    ];

    public function extractText(string $absoluteFilePath): string
    {
        $extension = strtolower(pathinfo($absoluteFilePath, PATHINFO_EXTENSION));

        try {
            // Check if extension is supported
            if (!in_array($extension, self::SUPPORTED_EXTENSIONS)) {
                Log::info("Skipping OCR for unsupported file type: {$extension} ({$absoluteFilePath})");
                return ''; // Return empty string for non-OCR files
            }

            if ($extension === 'pdf') {
                // Convert all PDF pages to images and extract text from each page
                return $this->extractTextFromPdf($absoluteFilePath);
            }

            // Image formats → Tesseract OCR with optimization flags
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'tiff', 'tif', 'bmp', 'webp'], true)) {
                return (new TesseractOCR($absoluteFilePath))
                    ->lang('fra','ara','eng') // French, Arabic and English languages
                    ->psm(3)  // Page Segmentation Mode 3: Fully automatic (best for mixed layouts)
                    ->oem(1)  // OCR Engine Mode 1: LSTM neural net (faster + more accurate than legacy)
                    ->run();
            }

            // Docx → structured text extraction (no image OCR needed)
            if ($extension === 'docx') {
                return $this->extractTextFromDocx($absoluteFilePath);
            }

            // Plain text / CSV → direct text read
            if (in_array($extension, ['txt', 'csv'], true)) {
                return $this->extractTextFromTextFile($absoluteFilePath);
            }

            // Excel → read first sheet into a flattened text representation
            if (in_array($extension, ['xls', 'xlsx'], true)) {
                return $this->extractTextFromExcel($absoluteFilePath);
            }

            // Fallback for any other "supported" type
            return '';
        } catch (Exception $e) {
            Log::error("OCR extraction failed for file {$absoluteFilePath}: " . $e->getMessage());
            throw $e; // rethrow so caller knows
        }
    }

    private function extractTextFromPdf(string $pdfPath): string
    {
        if (!file_exists($pdfPath)) {
            $msg = "File does not exist: {$pdfPath}";
            Log::error($msg);
            throw new Exception($msg);
        }

        if (!is_readable($pdfPath)) {
            $msg = "File is not readable: {$pdfPath}";
            Log::error($msg);
            throw new Exception($msg);
        }

        try {
            // First, determine page count and setup resolution
            $tempImagickForPages = new \Imagick();
            $tempImagickForPages->readImage($pdfPath);
            $totalPages = $tempImagickForPages->getNumberImages();
            $tempImagickForPages->clear();
            $tempImagickForPages->destroy();

            // Optimization: Use 300 DPI which is standard for OCR and uses significantly less memory than 500
            $resolution = 300; 

        } catch (Exception $e) {
            Log::error("Failed to load PDF {$pdfPath} or determine page count. File might be corrupted or encrypted: " . $e->getMessage());
            // If we can't even read the PDF (e.g. encrypted), we can't OCR it.
            // We return empty string instead of throwing to avoid crashing the queue repeatedly for a bad file.
            return ''; 
        }

        $fullText = '';

        // Process each page individually to avoid colorspace issues
        for ($pageIndex = 0; $pageIndex < $totalPages; $pageIndex++) {
            $outputPath = storage_path('app/tmp/' . Str::uuid() . "_page{$pageIndex}.png");
            $pageImagick = null;

            try {
                if (!file_exists(dirname($outputPath))) {
                    mkdir(dirname($outputPath), 0755, true);
                }

                // Log progress for large documents (every 5 pages to reduce log spam)
                if ($totalPages > 10 && ($pageIndex + 1) % 5 == 0) {
                    Log::info("OCR Progress: page " . ($pageIndex + 1) . " of {$totalPages}");
                }

                // Load individual page with resolution setting
                $pageImagick = new \Imagick();
                $pageImagick->setResolution($resolution, $resolution);
                $pageImagick->readImage($pdfPath . '[' . $pageIndex . ']');
                $pageImagick->setImageFormat('png');

                // Apply transformations to individual page with error handling
                try {
                    // Try to convert to grayscale for better OCR accuracy
                    $currentColorspace = $pageImagick->getImageColorspace();
                    
                    // Only attempt grayscale conversion if not already grayscale
                    if ($currentColorspace !== \Imagick::COLORSPACE_GRAY) {
                        try {
                            $pageImagick->setImageType(\Imagick::IMGTYPE_GRAYSCALE);
                        } catch (\ImagickException $e) {
                            // If grayscale conversion fails, try transforming colorspace first
                            Log::warning("Failed to set image type to grayscale for page {$pageIndex}, attempting colorspace transform: " . $e->getMessage());
                            try {
                                // Try to transform to RGB first, then grayscale
                                if ($currentColorspace === \Imagick::COLORSPACE_CMYK) {
                                    $pageImagick->transformImageColorspace(\Imagick::COLORSPACE_RGB);
                                }
                                $pageImagick->setImageType(\Imagick::IMGTYPE_GRAYSCALE);
                            } catch (\ImagickException $e2) {
                                // If all transformations fail, continue with original colorspace
                                Log::warning("Could not convert page {$pageIndex} to grayscale, using original colorspace: " . $e2->getMessage());
                                // Page will be processed in original colorspace
                            }
                        }
                    }

                    // Apply image enhancements in optimal order for OCR accuracy
                    // Note: Normalize FIRST to improve contrast before other operations
                    
                    try {
                        $pageImagick->normalizeImage(); // Step 1: Improve contrast (MOVED TO FIRST)
                    } catch (\ImagickException $e) {
                        Log::warning("Normalize failed for page {$pageIndex}: " . $e->getMessage());
                    }

                    try {
                        $pageImagick->despeckleImage(); // Step 2: Remove speckles early
                    } catch (\ImagickException $e) {
                        Log::warning("Despeckle failed for page {$pageIndex}: " . $e->getMessage());
                    }

                    try {
                        // Step 3: Enhanced deskewing with threshold (40% = 0.4 * full range)
                        // This detects and corrects rotation more aggressively than default
                        $pageImagick->deskewImage(0.4 * \Imagick::getQuantum());
                    } catch (\ImagickException $e) {
                        Log::warning("Deskew failed for page {$pageIndex}: " . $e->getMessage());
                    }

                    try {
                        $pageImagick->sharpenImage(0, 1.0); // Step 4: Sharpen text
                    } catch (\ImagickException $e) {
                        Log::warning("Sharpen failed for page {$pageIndex}: " . $e->getMessage());
                    }

                    try {
                        // Step 5: Adaptive thresholding for better binarization
                        $pageImagick->thresholdImage(0.5 * \Imagick::getQuantum());
                    } catch (\ImagickException $e) {
                        Log::warning("Threshold failed for page {$pageIndex}: " . $e->getMessage());
                    }

                } catch (\Exception $e) {
                    // If transformations fail, log but continue with basic image
                    Log::warning("Some transformations failed for page {$pageIndex}, continuing with basic processing: " . $e->getMessage());
                }

                // Write processed page to temporary file
                $pageImagick->writeImage($outputPath);

                // Verify image was created successfully
                if (!file_exists($outputPath) || filesize($outputPath) == 0) {
                    throw new Exception("Failed to convert page {$pageIndex} to image");
                }

                // OCR on the converted image with optimization flags
                $pageText = (new TesseractOCR($outputPath))
                    ->lang('fra','ara','eng')
                    ->psm(3)  // Fully automatic page segmentation
                    ->oem(1)  // LSTM neural net mode
                    ->run();

                $fullText .= $pageText . "\n";

            } catch (Exception $e) {
                Log::error("Failed to process page {$pageIndex} of PDF {$pdfPath}: " . $e->getMessage());
                // Skip this page and continue without adding error details to OCR text
            } finally {
                // Clean up page Imagick object
                if ($pageImagick) {
                    $pageImagick->clear();
                    $pageImagick->destroy();
                }
                
                // Ensure temporary image is always removed
                if (file_exists($outputPath)) {
                    unlink($outputPath);
                }
            }
        }

        return trim($fullText);
    }

    private function extractTextFromDocx(string $path): string
    {
        if (!file_exists($path) || !is_readable($path)) {
            Log::error("Docx file not accessible for OCR: {$path}");
            return '';
        }

        $lower = strtolower($path);
        if (!str_ends_with($lower, '.docx') || !class_exists('ZipArchive')) {
            Log::info("Skipping non-docx Office file for text extraction: {$path}");
            return '';
        }

        try {
            $zip = new \ZipArchive();
            if ($zip->open($path) !== true) {
                return '';
            }

            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            if ($xml === false) {
                return '';
            }

            // Convert paragraph and tab markers into simple text breaks
            $xml = preg_replace('/<w:p[^>]*>/', "\n", $xml);
            $xml = preg_replace('/<w:tab[^>]*>/', "\t", $xml);
            $text = strip_tags($xml);
            $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

            return trim($text);
        } catch (Exception $e) {
            Log::error("Failed to extract text from DOCX for OCR: {$path} - " . $e->getMessage());
            return '';
        }
    }

    private function extractTextFromTextFile(string $path): string
    {
        if (!file_exists($path) || !is_readable($path)) {
            Log::error("Text file not accessible for OCR: {$path}");
            return '';
        }

        $contents = @file_get_contents($path);
        if ($contents === false) {
            return '';
        }

        return trim($contents);
    }

    private function extractTextFromExcel(string $path): string
    {
        if (!file_exists($path) || !is_readable($path)) {
            Log::error("Excel file not accessible for OCR: {$path}");
            return '';
        }

        try {
            // Lazy-load dependency to avoid hard failure if PhpSpreadsheet is missing
            if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
                Log::warning('PhpSpreadsheet not available; skipping Excel OCR extraction.');
                return '';
            }

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();

            // Read a reasonable area (e.g. A1:Z100) to avoid huge memory usage
            $rows = $sheet->rangeToArray('A1:Z100', null, true, true, true);
            $lines = [];
            foreach ($rows as $row) {
                $cells = array_filter(array_map('trim', array_values($row)), fn($v) => $v !== null && $v !== '');
                if (!empty($cells)) {
                    $lines[] = implode(' ', $cells);
                }
            }

            return trim(implode("\n", $lines));
        } catch (Exception $e) {
            Log::error("Failed to extract text from Excel for OCR: {$path} - " . $e->getMessage());
            return '';
        }
    }
}
