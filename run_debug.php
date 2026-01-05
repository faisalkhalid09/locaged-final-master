<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

try {
    echo "Starting seeder...\n";
    Artisan::call('db:seed', ['--force' => true]);
    echo "Seeder completed.\n";
    echo Artisan::output();
} catch (\Throwable $e) {
    echo "Exception caught!\n";
    $output = "Message: " . $e->getMessage() . "\n";
    $output .= "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    $output .= "Trace:\n" . $e->getTraceAsString() . "\n";
    file_put_contents('debug_error.txt', $output);
    echo "Error detailed saved to debug_error.txt\n";
}
