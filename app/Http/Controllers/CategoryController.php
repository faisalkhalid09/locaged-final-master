<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    // List all categories
    public function index()
    {
        Gate::authorize('viewAny', Category::class);

        $user = auth()->user();

        $query = Category::with(['department'])
            ->withCount(['documents', 'subcategories']);

        // Global scope in Category model now handles all role-based filtering logic
        
        $categories = $query->get();

        return view('categories.index', compact('categories'));
    }

    // Show create form
    public function create()
    {
        Gate::authorize('create', Category::class);

        $user = auth()->user();
        
        // Get accessible service IDs using the same logic as physical locations
        $accessibleServiceIds = \App\Models\Box::getAccessibleServiceIds($user);
        
        if ($accessibleServiceIds === 'all') {
            // Super admins see all departments
            $departments = \App\Models\Department::with('subDepartments.services')->get();
        } else {
            // Filter to only show departments/sub-departments/services the user has access to
            $departments = \App\Models\Department::with(['subDepartments' => function($subQuery) use ($accessibleServiceIds) {
                $subQuery->whereHas('services', function($serviceQuery) use ($accessibleServiceIds) {
                    $serviceQuery->whereIn('id', $accessibleServiceIds);
                })->with(['services' => function($serviceQuery) use ($accessibleServiceIds) {
                    $serviceQuery->whereIn('id', $accessibleServiceIds);
                }]);
            }])->whereHas('subDepartments.services', function($serviceQuery) use ($accessibleServiceIds) {
                $serviceQuery->whereIn('id', $accessibleServiceIds);
            })->get();
        }

        return view('categories.create', compact('departments'));
    }

    // Store a new category
    public function store(Request $request)
    {
        Gate::authorize('create', Category::class);

        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'sub_department_id' => 'required|exists:sub_departments,id',
            'service_id' => 'required|exists:services,id',
            'category_name' => [
                'required',
                'string',
                'max:255',
                // Category names are unique per service, but can repeat across services
                Rule::unique('categories', 'name')->where(function ($query) use ($request) {
                    return $query->where('service_id', $request->input('service_id'));
                }),
            ],
            'subcategories' => 'nullable|array',
            'subcategories.*' => 'required|string|max:255',
            'expiry_value' => 'required|integer|min:1',
            'expiry_unit' => 'required|string|in:days,months,years',
        ]);


        $category = Category::create([
            'name' => $data['category_name'],
            'department_id' => $data['department_id'],
            'sub_department_id' => $data['sub_department_id'],
            'service_id' => $data['service_id'],
            'expiry_value' => $data['expiry_value'] ?? null,
            'expiry_unit' => $data['expiry_unit'] ?? null,
        ]);

        // Only create subcategories if they are provided
        if ($request->has('subcategories') && !empty($request->get('subcategories'))) {
            foreach ($request->get('subcategories') as $subcategory) {
                if (!empty(trim($subcategory))) { // Only create if subcategory is not empty
                    Subcategory::create([
                        'name' => $subcategory,
                        'category_id' => $category->id
                    ]);
                }
            }
        }



        return redirect()->route('categories.index')->with('success', 'Category created.');
    }


    // Show edit form
    public function edit(Category $category)
    {
        Gate::authorize('update', $category);

        $user = auth()->user();
        
        // Get accessible service IDs using the same logic as physical locations
        $accessibleServiceIds = \App\Models\Box::getAccessibleServiceIds($user);
        
        if ($accessibleServiceIds === 'all') {
            $departments = \App\Models\Department::with('subDepartments.services')->get();
        } else {
            // Filter to only show departments/sub-departments/services the user has access to
            $departments = \App\Models\Department::with(['subDepartments' => function($subQuery) use ($accessibleServiceIds) {
                $subQuery->whereHas('services', function($serviceQuery) use ($accessibleServiceIds) {
                    $serviceQuery->whereIn('id', $accessibleServiceIds);
                })->with(['services' => function($serviceQuery) use ($accessibleServiceIds) {
                    $serviceQuery->whereIn('id', $accessibleServiceIds);
                }]);
            }])->whereHas('subDepartments.services', function($serviceQuery) use ($accessibleServiceIds) {
                $serviceQuery->whereIn('id', $accessibleServiceIds);
            })->get();
        }

        return view('categories.edit', compact('category', 'departments'));
    }


    // Update a category
    public function update(Request $request, Category $category)
    {
        Gate::authorize('update', $category);

        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'sub_department_id' => 'required|exists:sub_departments,id',
            'service_id' => 'required|exists:services,id',
            'sub_department_id' => 'required|exists:sub_departments,id',
            'service_id' => 'required|exists:services,id',
            'name' => [
                'required',
                'string',
                'max:255',
                // Ensure uniqueness per service while allowing duplicates across services
                Rule::unique('categories', 'name')
                    ->ignore($category->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('service_id', $request->input('service_id'));
                    }),
            ],
            'subcategories_id' => 'nullable|array',
            'subcategories_id.*' => 'nullable|integer|exists:subcategories,id',
            'subcategories_name' => 'nullable|array',
            'subcategories_name.*' => 'required|string|max:255',
            'expiry_value' => 'required|integer|min:1',
            'expiry_unit' => 'required|string|in:days,months,years',
        ]);

        // Update category name and hierarchy
        $category->name = $data['name'];
        $category->department_id = $data['department_id'];
        $category->sub_department_id = $data['sub_department_id'];
        $category->service_id = $data['service_id'];
        $category->expiry_value = $data['expiry_value'] ?? null;
        $category->expiry_unit = $data['expiry_unit'] ?? null;
        $category->save();

        $submittedIds = collect($data['subcategories_id'] ?? []);
        $submittedNames = collect($data['subcategories_name'] ?? []);

        // Find subcategories currently in DB
        $existingSubIds = $category->subcategories()->pluck('id');

        // Delete removed subcategories
        $toDelete = $existingSubIds->diff($submittedIds->filter(fn($id) => $id !== null));
        $category->subcategories()->whereIn('id', $toDelete)->delete();

        // Update or create subcategories
        foreach ($submittedNames->values() as $index => $name) {
            $id = $submittedIds[$index] ?? null;

            if ($id) {
                // Update existing subcategory
                $subcategory = $category->subcategories()->find($id);
                if ($subcategory) {
                    $subcategory->update(['name' => $name]);
                }
            } else {
                // Create new subcategory
                $category->subcategories()->create(['name' => $name]);
            }
        }

        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }


    // Delete a category
    public function destroy(Category $category)
    {
        Gate::authorize('delete', $category);

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }

    // Show subcategories for a category
    public function subcategories(Category $category)
    {
        Gate::authorize('view', $category);
        
        $subcategories = $category->subcategories()->withCount('documents')->get();
        
        return view('categories.subcategories', compact('category', 'subcategories'));
    }
}
