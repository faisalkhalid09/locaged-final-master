<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Models\Department;
use App\Models\WorkFlowRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class WorkFlowRuleController extends Controller
{
    public function byDepartment($departmentId)
    {
        $department = Department::findOrFail($departmentId);

        $rules = WorkFlowRule::with('department')->where('department_id', $departmentId)->paginate(10);
        return view('workflow_rules.by-department', compact('rules', 'department'));
    }

    public function store(Request $request, $departmentId)
    {

        $validated = $request->validate([
            'from_status' => [
                'required',
                'different:to_status',
                new Enum(DocumentStatus::class),
                Rule::unique('workflow_rules')
                    ->where(function ($query) use ($departmentId, $request) {
                        return $query->where('department_id', $departmentId)
                            ->where('to_status', $request->to_status);
                    }),
            ],
            'to_status' => ['required', new Enum(DocumentStatus::class)],
        ]);

        $department = Department::findOrFail($departmentId);

        $validated['department_id'] = $department->id;
        WorkFlowRule::create($validated);

        return redirect()->back()->with('success', 'Workflow rule created successfully.');
    }


    public function update(Request $request, WorkFlowRule $workflowRule)
    {
        $validated = $request->validate([
            'from_status' => [
                'required',
                new Enum(DocumentStatus::class),
                Rule::unique('workflow_rules')
                    ->ignore($workflowRule->id)
                    ->where(function ($query) use ($workflowRule, $request) {
                        return $query->where('department_id', $workflowRule->department_id)
                        ->where('to_status', $request->to_status);
                    }),
                'different:to_status',
            ],
            'to_status' => [
                'required',
                new Enum(DocumentStatus::class),
            ],
        ]);


        $workflowRule->update($validated);

        return redirect()->back()->with('success', 'Workflow rule updated successfully.');
    }

    public function destroy(WorkFlowRule $workflowRule)
    {
        $workflowRule->delete();

        return redirect()->back()->with('success', 'Workflow rule deleted successfully.');
    }
}
