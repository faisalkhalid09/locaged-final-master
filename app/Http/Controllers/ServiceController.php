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
        Gate::authorize('create', Service::class);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sub_department_id' => 'required|exists:sub_departments,id',
        ]);

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
