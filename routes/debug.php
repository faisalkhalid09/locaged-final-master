<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

Route::get('/debug-chart-data', function () {
    $controller = new HomeController();
    $reflection = new ReflectionClass($controller);
    
    $weeklyMethod = $reflection->getMethod('getWeeklyData');
    $weeklyMethod->setAccessible(true);
    
    $monthlyMethod = $reflection->getMethod('getMonthlyData');
    $monthlyMethod->setAccessible(true);
    
    return response()->json([
        'weekly' => $weeklyMethod->invoke($controller),
        'monthly' => $monthlyMethod->invoke($controller),
    ]);
});
