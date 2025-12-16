<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Category;
use App\Models\Subcategory;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create default subcategories for categories that don't have any
        $categories = Category::whereDoesntHave('subcategories')->get();
        
        foreach ($categories as $category) {
            Subcategory::create([
                'name' => 'General',
                'description' => 'Default subcategory for ' . $category->name,
                'category_id' => $category->id
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove default subcategories created for categories without subcategories
        Subcategory::where('name', 'General')
            ->whereHas('category', function($query) {
                $query->whereDoesntHave('subcategories', function($subQuery) {
                    $subQuery->where('name', '!=', 'General');
                });
            })
            ->delete();
    }
};