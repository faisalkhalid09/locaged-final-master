<?php

namespace Database\Seeders;

use App\Models\Box;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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
        $categories = \App\Models\Category::withoutGlobalScopes()->get();
        $departments = \App\Models\Department::all();
        $services = \App\Models\Service::all();
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

        // Document types
        $documentTemplates = [
            'Contract', 'Invoice', 'Report', 'Proposal', 'Certificate',
            'Policy', 'Manual', 'Receipt', 'Letter', 'Memo', 'Minutes', 'Budget'
        ];

        $statuses = ['pending', 'approved', 'declined', 'archived'];
        
        $count = $this->command->ask('How many documents to create?', 1050);
        
        $this->command->info("Creating {$count} realistic test documents...");
        $progressBar = $this->command->getOutput()->createProgressBar($count);

        for ($i = 1; $i <= $count; $i++) {
            $templateType = $documentTemplates[array_rand($documentTemplates)];
            $title = "{$templateType} - " . date('Y') . "-" . str_pad($i, 4, '0', STR_PAD_LEFT);
            
            $createdAt = now()->subDays(rand(1, 365));
            $expireAt = $createdAt->copy()->addMonths(rand(6, 24));
            
            $category = $categories->isNotEmpty() ? $categories->random() : null;
            $department = $departments->isNotEmpty() ? $departments->random() : null;
            $service = $services->isNotEmpty() ? $services->random() : null;
            $box = $boxes->isNotEmpty() ? $boxes->random() : null;

            // Insert document directly
            $documentId = DB::table('documents')->insertGetId([
                'uid' => (string) Str::uuid(),
                'title' => $title,
                'status' => $statuses[array_rand($statuses)],
                'category_id' => $category?->id,
                'department_id' => $department?->id,
                'service_id' => $service?->id,
                'box_id' => $box?->id,
                'created_by' => $users->random()->id,
                'created_at' => $createdAt,
                'updated_at' => now(),
                'expire_at' => rand(0, 10) > 7 ? $expireAt : null,
                'is_expired' => 0,
                'metadata' => json_encode([
                    'author' => fake()->name(),
                    'keywords' => implode(', ', fake()->words(3)),
                    'subject' => $templateType,
                ]),
            ]);

            // Create PDF file
            $fileName = Str::slug($title) . '-v1.pdf';
            $filePath = 'documents/' . date('Y/m', strtotime($createdAt)) . '/' . $fileName;
            $pdfContent = $this->generateRealisticPDF($title, $templateType, $i, $box);
            Storage::put($filePath, $pdfContent);

            // Insert document version directly
            DB::table('document_versions')->insert([
                'document_id' => $documentId,
                'version_number' => 1.0,
                'file_path' => $filePath,
                'file_type' => 'pdf',
                'uploaded_by' => $users->random()->id,
                'uploaded_at' => $createdAt,
                'created_at' => $createdAt,
                'updated_at' => now(),
            ]);

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->command->newLine();
        $this->command->info("Successfully created {$count} realistic test documents!");
    }

    private function generateRealisticPDF(string $title, string $type, int $docNum, $box): string
    {
        $date = date('F d, Y');
        $location = $box ? $box->__toString() : 'Unassigned';
        
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
/Length 500
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
(Document: {$docNum}) Tj
0 -20 Td
(Type: {$type}) Tj
0 -20 Td
(Location: {$location}) Tj
0 -40 Td
(This is a realistic test document for system testing.) Tj
0 -15 Td
(It contains valid PDF structure and can be previewed.) Tj
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
915
%%EOF
PDF;

        return $content;
    }
}
