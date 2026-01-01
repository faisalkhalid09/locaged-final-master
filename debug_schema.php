<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = Schema::getColumnListing('document_versions');
$output = "Columns in document_versions table:\n";
foreach ($columns as $column) {
    // Get detailed info using DB select since Schema::getColumnType is limited
    $info = DB::select("SHOW COLUMNS FROM document_versions WHERE Field = ?", [$column])[0];
    $output .= "{$column}: Type={$info->Type}, Null={$info->Null}, Default=" . ($info->Default ?? 'NULL') . "\n";
}
file_put_contents('schema_dump.txt', $output);
echo "Schema written to schema_dump.txt\n";

