<?php

namespace App\Http\Controllers;

use App\Models\Subcategory;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SubcategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('viewAny', Category::class);
        
        // Filter subcategories by user's department access
        $user = auth()->user();
        $subcategories = Subcategory::with(['category', 'documents'])
            ->whereHas('category', function($query) use ($user) {
                if (!$user->can('view any category')) {
                    $query->whereIn('department_id', $user->departments->pluck('id'));
                }
            })
            ->withCount('documents')
            ->get();
            
        return view('subcategories.index', compact('subcategories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Category::class);
        
        $categories = Category::all();
        return view('subcategories.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Category::class);
        
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
        ]);

        Subcategory::create($data);

        return redirect()->route('subcategories.index')->with('success', 'Subcategory created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Subcategory $subcategory)
    {
        Gate::authorize('view', $subcategory->category);
        
        $subcategory->load(['category', 'documents']);
        return view('subcategories.show', compact('subcategory'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Subcategory $subcategory)
    {
        Gate::authorize('update', $subcategory->category);
        
        $categories = Category::all();
        return view('subcategories.edit', compact('subcategory', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Subcategory $subcategory)
    {
        Gate::authorize('update', $subcategory->category);
        
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
        ]);

        $subcategory->update($data);

        return redirect()->route('subcategories.index')->with('success', 'Subcategory updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subcategory $subcategory)
    {
        Gate::authorize('delete', $subcategory->category);
        
        $categoryId = $subcategory->category_id; // Store category ID before deletion
        $subcategory->delete();

        return redirect()->route('categories.subcategories', $categoryId)->with('success', 'Subcategory deleted successfully.');
    }
}