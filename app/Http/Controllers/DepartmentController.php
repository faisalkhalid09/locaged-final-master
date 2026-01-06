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

        $user = auth()->user();

        // Check if user is Admin de pole
        $isAdminDePole = $user?->hasRole('Admin de pole');
        
        // Admin de pole CAN create sub-structures (sub-depts, services) but NOT new poles
        $canCreateStructures = true;
        $canCreatePole = !$isAdminDePole; // Only higher admins can create new poles

        // Eager-load sub-departments and services for tree view
        $departmentsQuery = Department::with('subDepartments.services');

        // Filter to only assigned departments for Admin de pole
        if ($isAdminDePole && $user->departments && $user->departments->isNotEmpty()) {
            $departmentsQuery->whereIn('id', $user->departments->pluck('id'));
        }

        $departments = $departmentsQuery->latest()->paginate(10);

        // For Admin de pole: only show their assigned departments in dropdowns
        // For other admins: show all departments
        if ($isAdminDePole && $user->departments && $user->departments->isNotEmpty()) {
            $allDepartments = Department::with('subDepartments.services')
                ->whereIn('id', $user->departments->pluck('id'))
                ->orderBy('name')
                ->get();
        } else {
            $allDepartments = Department::with('subDepartments.services')->orderBy('name')->get();
        }

        return view('departments.index', compact('departments', 'allDepartments', 'canCreateStructures', 'canCreatePole'));
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
