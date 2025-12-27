<?php

namespace Database\Seeders;

use App\Models\Box;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TestDocumentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting to seed test documents...');

        // Get all available options
        $users = User::all();
        $categories = Category::all();
        $departments = Department::all();
        $services = Service::all();
        $boxes = Box::all();

        if ($users->isEmpty()) {
            $this->command->error('No users found. Please seed users first.');
            return;
        }

        // File types and their extensions
        $fileTypes = [
            'pdf' => ['application/pdf', '.pdf'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', '.docx'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', '.xlsx'],
            'png' => ['image/png', '.png'],
            'jpg' => ['image/jpeg', '.jpg'],
        ];

        $statuses = ['draft', 'pending', 'approved', 'declined', 'archived'];

        // Sample document titles
        $titlePrefixes = [
            'Contract', 'Invoice', 'Report', 'Proposal', 'Agreement', 'Policy', 'Manual',
            'Receipt', 'Certificate', 'Letter', 'Memo', 'Minutes', 'Presentation', 'Analysis',
            'Budget', 'Statement', 'Plan', 'Summary', 'Review', 'Form', 'Application'
        ];

        $this->command->info('Creating 1000 test documents...');
        $progressBar = $this->command->getOutput()->createProgressBar(1000);

        for ($i = 1; $i <= 1000; $i++) {
            // Randomly select file type
            $fileTypeKey = array_rand($fileTypes);
            [$mimeType, $extension] = $fileTypes[$fileTypeKey];

            // Generate document title
            $titlePrefix = $titlePrefixes[array_rand($titlePrefixes)];
            $title = "{$titlePrefix} {$i} - " . Str::random(5);

            // Random dates
            $createdAt = now()->subDays(rand(1, 365));
            $expireAt = $createdAt->copy()->addDays(rand(30, 730));

            // Create document
            $document = Document::create([
                'title' => $title,
                'description' => "Test document {$i} - " . fake()->sentence(),
                'status' => $statuses[array_rand($statuses)],
                'category_id' => $categories->isNotEmpty() ? $categories->random()->id : null,
                'department_id' => $departments->isNotEmpty() ? $departments->random()->id : null,
                'service_id' => $services->isNotEmpty() ? $services->random()->id : null,
                'box_id' => $boxes->isNotEmpty() ? $boxes->random()->id : null,
                'created_by' => $users->random()->id,
                'created_at' => $createdAt,
                'expire_at' => rand(0, 1) ? $expireAt : null,
                'is_expired' => false,
                'metadata' => [
                    'author' => fake()->name(),
                    'keywords' => fake()->words(3, true),
                ],
            ]);

            // Create dummy file
            $fileName = Str::slug($title) . '-v1' . $extension;
            $filePath = 'documents/' . date('Y/m', strtotime($createdAt)) . '/' . $fileName;

            // Create minimal dummy file content
            $dummyContent = $this->createDummyFileContent($fileTypeKey, $title, $i);
            Storage::put($filePath, $dummyContent);

            // Create document version
            DocumentVersion::create([
                'document_id' => $document->id,
                'version_number' => 1,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size' => strlen($dummyContent),
                'file_type' => $fileTypeKey,
                'mime_type' => $mimeType,
                'uploaded_by' => $users->random()->id,
                'is_current' => true,
                'created_at' => $createdAt,
            ]);

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->command->newLine();
        $this->command->info('Successfully created 1000 test documents!');
    }

    /**
     * Create minimal dummy file content
     */
    private function createDummyFileContent(string $fileType, string $title, int $index): string
    {
        switch ($fileType) {
            case 'pdf':
                // Minimal PDF structure
                return "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000056 00000 n\n0000000115 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n210\n%%EOF";

            case 'docx':
            case 'xlsx':
                // Minimal Office XML (just a placeholder)
                return "PK\x03\x04\x14\x00\x00\x00\x08\x00Test {$fileType} document: {$title}";

            case 'png':
                // Minimal 1x1 PNG
                return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

            case 'jpg':
                // Minimal JPEG
                return base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAIBAQIBAQICAgICAgICAwUDAwMDAwYEBAMFBwYHBwcGBwcICQsJCAgKCAcHCg0KCgsMDAwMBwkODw0MDgsMDAz/2wBDAQICAgMDAwYDAwYMCAcIDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAz/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCwAA0A');

            default:
                return "Test document {$index}: {$title}";
        }
    }
}
