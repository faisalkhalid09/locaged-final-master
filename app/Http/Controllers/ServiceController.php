<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ServiceController extends Controller
{
    /**
     * Store a new service.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $isAdminDePole = $user?->hasRole('Admin de pole');

        // Admin de pole can create services in sub-departments within their assigned pole
        if ($isAdminDePole) {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'sub_department_id' => 'required|exists:sub_departments,id',
            ]);

            // Verify the sub-department belongs to a department assigned to this Admin de pole
            $assignedDeptIds = $user->departments->pluck('id')->toArray();
            $subDept = \App\Models\SubDepartment::find($data['sub_department_id']);
            
            if (!$subDept || !in_array($subDept->department_id, $assignedDeptIds)) {
                abort(403, 'You can only create services in your assigned pole.');
            }
        } else {
            Gate::authorize('create', Service::class);
            
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'sub_department_id' => 'required|exists:sub_departments,id',
            ]);
        }

        Service::create($data);

        return redirect()->route('departments.index')->with('success', 'Service created.');
    }

    /**
     * Update an existing service.
     */
    public function update(Request $request, Service $service)
    {
        Gate::authorize('update', $service);

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $service->update($data);

        return redirect()->route('departments.index')->with('success', 'Service updated.');
    }

    /**
     * Delete a service.
     */
    public function destroy(Service $service)
    {
        Gate::authorize('delete', $service);

        $service->delete();

        return redirect()->route('departments.index')->with('success', 'Service deleted.');
    }
}
