<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PdfConversionService
{
    /**
     * Convert a document (Word/Excel) to PDF using LibreOffice
     * 
     * @param string $filePath Path to the original file in storage
     * @return string|null Path to the converted PDF, or null on failure
     */
    public function convertToPdf($filePath)
    {
        $absolutePath = Storage::disk('local')->path($filePath);
        
        if (!file_exists($absolutePath)) {
            Log::error('File not found for PDF conversion', ['path' => $filePath]);
            return null;
        }

        // Determine output PDF path
        $pathInfo = pathinfo($filePath);
        $pdfFileName = $pathInfo['filename'] . '.pdf';
        $outputDir = Storage::disk('local')->path($pathInfo['dirname']);
        $pdfPath = $pathInfo['dirname'] . '/' . $pdfFileName;
        $absolutePdfPath = $outputDir . '/' . $pdfFileName;

        // Check if PDF already exists
        if (Storage::disk('local')->exists($pdfPath)) {
            return $pdfPath;
        }

        // Determine file type
        $extension = strtolower($pathInfo['extension'] ?? '');
        
        try {
            // Try PhpSpreadsheet for Excel files first (doesn't require LibreOffice)
            if (in_array($extension, ['xlsx', 'xls', 'csv'])) {
                if ($this->convertExcelToPdfWithPhpSpreadsheet($absolutePath, $absolutePdfPath)) {
                    Log::info('PDF conversion successful (PhpSpreadsheet)', [
                        'original' => $filePath,
                        'pdf' => $pdfPath
                    ]);
                    return $pdfPath;
                }
            }
            
            // Fall back to LibreOffice for Word and Excel if PhpSpreadsheet fails
            if ($this->isLibreOfficeAvailable()) {
                // Generate a unique temporary directory for LibreOffice user profile
                // This prevents permission errors when www-data doesn't have a home directory
                $uniqueId = uniqid('lo_', true);
                $tempUserDir = sys_get_temp_dir() . '/LibreOffice_Conversion_' . $uniqueId;
                
                // LibreOffice command to convert to PDF
                // -env:UserInstallation: use a custom user profile location
                // --headless: run without GUI
                // --convert-to pdf: convert to PDF format
                // --outdir: output directory
                $process = new Process([
                    'soffice',
                    '-env:UserInstallation=file://' . $tempUserDir,
                    '--headless',
                    '--convert-to',
                    'pdf',
                    '--outdir',
                    $outputDir,
                    $absolutePath
                ]);

                $process->setTimeout(120); // 2 minutes timeout
                $process->run();

                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }

                // Verify the PDF was created
                if (file_exists($absolutePdfPath)) {
                    Log::info('PDF conversion successful (LibreOffice)', [
                        'original' => $filePath,
                        'pdf' => $pdfPath
                    ]);
                    return $pdfPath;
                } else {
                    Log::error('PDF file not created after conversion', ['expected_path' => $absolutePdfPath]);
                    return null;
                }
            } else {
                Log::warning('LibreOffice not available for PDF conversion', ['file' => $filePath]);
                return null;
            }

        } catch (\Exception $e) {
            Log::error('PDF conversion failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Convert Excel file to PDF using PhpSpreadsheet + mPDF
     * 
     * @param string $inputPath Absolute path to Excel file
     * @param string $outputPath Absolute path for PDF output
     * @return bool Success status
     */
    private function convertExcelToPdfWithPhpSpreadsheet($inputPath, $outputPath)
    {
        try {
            if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
                return false;
            }
            
            // Load spreadsheet
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputPath);
            
            // Configure PDF writer
            \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf::class);
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Pdf');
            
            // Save to PDF
            $writer->save($outputPath);
            
            return file_exists($outputPath);
            
        } catch (\Exception $e) {
            Log::error('PhpSpreadsheet PDF conversion failed', [
                'input' => $inputPath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if LibreOffice is installed and available
     * 
     * @return bool
     */
    public function isLibreOfficeAvailable()
    {
        $process = new Process(['soffice', '--version']);
        $process->run();

        return $process->isSuccessful();
    }
}
