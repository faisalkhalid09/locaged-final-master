<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DepartmentController extends Controller
{
    // List all departments and related hierarchy
    public function index()
    {
        Gate::authorize('viewAny', Department::class);

        // Legacy admin role should not access Structures page
        if (auth()->user()?->hasRole('admin')) {
            abort(403);
        }

        // Eager-load sub-departments and services for tree view
        $departments = Department::with('subDepartments.services')
            ->latest()
            ->paginate(10);

        // Full list for creation dropdowns
        $allDepartments = Department::orderBy('name')->get();

        return view('departments.index', compact('departments', 'allDepartments'));
    }


    // Store a new department
    public function store(Request $request)
    {
        Gate::authorize('create', Department::class);


        $data = $request->validate([
            'name'        => 'required|string|max:255|unique:departments,name',
            'description' => 'nullable|string|max:1000',
        ]);

        Department::create($data);

        return redirect()->route('departments.index')->with('success', 'Department created.');
    }



    // Update a department
    public function update(Request $request, Department $department)
    {
        // Authorize against the specific department model instance
        Gate::authorize('update', $department);

        $data = $request->validate([
            'name'        => 'required|string|max:255|unique:departments,name,' . $department->id,
            'description' => 'nullable|string|max:1000',
        ]);

        $department->update($data);

        return redirect()->route('departments.index')->with('success', 'Department updated.');
    }

    // Delete a department
    public function destroy(Department $department)
    {
        // Authorize against the specific department model instance
        Gate::authorize('delete', $department);

        $department->delete();

        return redirect()->route('departments.index')->with('success', 'Department deleted.');
    }
}
