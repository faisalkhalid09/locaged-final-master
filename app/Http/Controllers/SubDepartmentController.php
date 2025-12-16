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
        Gate::authorize('create', Department::class);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
        ]);

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
