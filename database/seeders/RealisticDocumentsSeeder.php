<?php

namespace Database\Seeders;

use App\Models\Box;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Service;
use App\Models\SubDepartment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RealisticDocumentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting to seed realistic test documents...');

        // Get all available options from your actual database
        $users = User::all();
        $categories = Category::all();
        $departments = Department::all();
        $subDepartments = SubDepartment::all();
        $services = Service::all();
        $boxes = Box::with('shelf.row.room')->get();

        if ($users->isEmpty()) {
            $this->command->error('No users found. Please seed users first.');
            return;
        }

        $this->command->info("Found:");
        $this->command->info("- {$users->count()} users");
        $this->command->info("- {$categories->count()} categories");
        $this->command->info("- {$departments->count()} departments");
        $this->command->info("- {$services->count()} services");
        $this->command->info("- {$boxes->count()} boxes");

        // Realistic document types and templates
        $documentTemplates = [
            'Contract' => ['pdf', 'approved', 'Legal contract document for services'],
            'Invoice' => ['pdf', 'approved', 'Financial invoice for payment'],
            'Report' => ['pdf', 'pending', 'Monthly business report'],
            'Proposal' => ['pdf', 'draft', 'Project proposal document'],
            'Certificate' => ['pdf', 'approved', 'Official certificate of completion'],
            'Policy' => ['pdf', 'approved', 'Company policy document'],
            'Manual' => ['pdf', 'approved', 'User manual and guidelines'],
            'Receipt' => ['pdf', 'approved', 'Payment receipt document'],
            'Letter' => ['pdf', 'pending', 'Official correspondence letter'],
            'Memo' => ['pdf', 'pending', 'Internal memorandum'],
            'Minutes' => ['pdf', 'approved', 'Meeting minutes document'],
            'Budget' => ['pdf', 'draft', 'Annual budget planning'],
            'Form' => ['pdf', 'pending', 'Application form document'],
        ];

        $statuses = ['draft', 'pending', 'approved', 'declined', 'archived'];
        
        $count = $this->command->ask('How many documents to create?', 1000);
        
        $this->command->info("Creating {$count} realistic test documents...");
        $progressBar = $this->command->getOutput()->createProgressBar($count);

        for ($i = 1; $i <= $count; $i++) {
            // Select random template
            $templateType = array_rand($documentTemplates);
            [$fileType, $defaultStatus, $description] = $documentTemplates[$templateType];

            // Generate realistic title
            $title = "{$templateType} - " . date('Y') . "-" . str_pad($i, 4, '0', STR_PAD_LEFT);
            
            // Random dates (spread over last year)
            $createdAt = now()->subDays(rand(1, 365));
            $expireAt = $createdAt->copy()->addMonths(rand(6, 24));
            
            // Randomly select from actual hierarchy
            $category = $categories->isNotEmpty() ? $categories->random() : null;
            $department = $departments->isNotEmpty() ? $departments->random() : null;
            $service = $services->isNotEmpty() ? $services->random() : null;
            $box = $boxes->isNotEmpty() ? $boxes->random() : null;

            // Create document
            $document = Document::create([
                'title' => $title,
                'status' => $statuses[array_rand($statuses)],
                'category_id' => $category?->id,
                'subcategory_id' => $category ? $category->subcategories->random()?->id : null,
                'department_id' => $department?->id,
                'service_id' => $service?->id,
                'box_id' => $box?->id,
                'created_by' => $users->random()->id,
                'created_at' => $createdAt,
                'expire_at' => rand(0, 10) > 7 ? $expireAt : null, // 30% have expiration
                'is_expired' => false,
                'metadata' => [
                    'author' => fake()->name(),
                    'keywords' => implode(', ', fake()->words(3)),
                    'subject' => $templateType,
                    'description' => $description . " - Document #{$i}", // Store in metadata instead
                ],
            ]);

            // Create realistic PDF file
            $fileName = Str::slug($title) . '-v1.pdf';
            $filePath = 'documents/' . date('Y/m', strtotime($createdAt)) . '/' . $fileName;

            // Generate simple but valid PDF
            $pdfContent = $this->generateRealisticPDF($title, $description, $templateType, $i, $box);
            Storage::put($filePath, $pdfContent);

            // Create document version
            DocumentVersion::create([
                'document_id' => $document->id,
                'version_number' => 1,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size' => strlen($pdfContent),
                'file_type' => 'pdf',
                'mime_type' => 'application/pdf',
                'uploaded_by' => $users->random()->id,
                'is_current' => true,
                'created_at' => $createdAt,
            ]);

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->command->newLine();
        $this->command->info("Successfully created {$count} realistic test documents!");
        $this->command->info("Documents are distributed across:");
        $this->command->info("- All your categories");
        $this->command->info("- All your departments and services");
        $this->command->info("- All your physical box locations");
    }

    /**
     * Generate a simple but valid and previewable PDF document
     */
    private function generateRealisticPDF(string $title, string $description, string $type, int $docNum, $box): string
    {
        $date = date('F d, Y');
        $location = $box ? $box->__toString() : 'Unassigned';
        
        // Simple but valid PDF with actual content
        $content = <<<PDF
%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj

2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj

3 0 obj
<<
/Type /Page
/Parent 2 0 R
/Resources <<
/Font <<
/F1 <<
/Type /Font
/Subtype /Type1
/BaseFont /Helvetica
>>
/F2 <<
/Type /Font
/Subtype /Type1
/BaseFont /Helvetica-Bold
>>
>>
>>
/MediaBox [0 0 612 792]
/Contents 4 0 R
>>
endobj

4 0 obj
<<
/Length 600
>>
stream
BT
/F2 16 Tf
50 750 Td
({$title}) Tj
0 -30 Td
/F1 10 Tf
(Generated: {$date}) Tj
0 -20 Td
(Document Number: {$docNum}) Tj
0 -20 Td
(Document Type: {$type}) Tj
0 -20 Td
(Physical Location: {$location}) Tj
0 -40 Td
/F2 12 Tf
(Description:) Tj
0 -20 Td
/F1 10 Tf
({$description}) Tj
0 -40 Td
(This is a realistic test document generated for system testing.)Tj
0 -15 Td
(It contains actual PDF structure and can be properly previewed.)Tj
0 -15 Td
(The document management system should be able to display this.)Tj
0 -40 Td
/F2 10 Tf
(--- Document Content ---) Tj
0 -25 Td
/F1 10 Tf
(Lorem ipsum dolor sit amet, consectetur adipiscing elit.) Tj
0 -15 Td
(Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.) Tj
0 -15 Td
(Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.) Tj
0 -15 Td
(Duis aute irure dolor in reprehenderit in voluptate velit esse.) Tj
0 -15 Td
(Excepteur sint occaecat cupidatat non proident, sunt in culpa.) Tj
ET
endstream
endobj

xref
0 5
0000000000 65535 f
0000000009 00000 n
0000000058 00000 n
0000000115 00000 n
0000000364 00000 n
trailer
<<
/Size 5
/Root 1 0 R
>>
startxref
1015
%%EOF
PDF;

        return $content;
    }
}
