<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\SubDepartment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SubDepartmentController extends Controller
{
    /**
     * Store a new sub-department.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $isAdminDePole = $user?->hasRole('Admin de pole');

        // Admin de pole can create sub-departments in their assigned departments
        if ($isAdminDePole) {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'department_id' => 'required|exists:departments,id',
            ]);

            // Verify the department is assigned to this Admin de pole
            $assignedDeptIds = $user->departments->pluck('id')->toArray();
            if (!in_array($data['department_id'], $assignedDeptIds)) {
                abort(403, 'You can only create sub-departments in your assigned pole.');
            }
        } else {
            Gate::authorize('create', Department::class);
            
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'department_id' => 'required|exists:departments,id',
            ]);
        }

        SubDepartment::create($data);

        return redirect()->route('departments.index')->with('success', 'Sub-department created.');
    }

    /**
     * Update an existing sub-department.
     */
    public function update(Request $request, SubDepartment $sub_department)
    {
        Gate::authorize('update', Department::class);

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $sub_department->update($data);

        return redirect()->route('departments.index')->with('success', 'Sub-department updated.');
    }

    /**
     * Delete a sub-department.
     */
    public function destroy(SubDepartment $sub_department)
    {
        Gate::authorize('delete', Department::class);

        $sub_department->delete();

        return redirect()->route('departments.index')->with('success', 'Sub-department deleted.');
    }
}
