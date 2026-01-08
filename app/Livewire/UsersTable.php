<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\User;
use App\Support\RoleHierarchy;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UsersTable extends Component
{
    use WithPagination;

    public $search = '';
    public $role = '';
    public $department = '';
    public $dateFrom = '';
    public $dateTo = '';

    public $usersIds = [];
    public $checkedUsers = []; // IDs of selected users
    public $selectAll = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'role' => ['except' => ''],
        'department' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function updated($field)
    {
        // Reset to first page when any filter changes
        if (in_array($field, ['search', 'role', 'department', 'dateFrom', 'dateTo'])) {
            $this->resetPage();
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->role = '';
        $this->department = '';
        $this->dateFrom = '';
        $this->dateTo = '';

        $this->resetPage();
    }


    public function updatedSelectAll($value)
    {

        if ($value) {
            $this->checkedUsers = $this->usersIds;
        } else {
            $this->checkedUsers = [];
        }
    }


    public function render()
    {
        $usersQuery = User::with(['departments','roles']);
        
        $actor = auth()->user();
        
        // Priority 1: Admin de departments - ALWAYS filter by sub-departments
        // This must come FIRST to prevent them from seeing all users via broad permissions
        if ($actor->hasAnyRole(['Admin de departments', 'Division Chief'])) {
            // Sub-Department level visibility: users from same sub-departments ONLY
            $subDeptIds = $actor->subDepartments->pluck('id')->toArray();
            
            if (!empty($subDeptIds)) {
                // Show ONLY users who are assigned to one of these sub-departments
                $usersQuery->whereHas('subDepartments', function ($sq) use ($subDeptIds) {
                    $sq->whereIn('sub_departments.id', $subDeptIds);
                });
            } else {
                $usersQuery->whereRaw('1 = 0'); // Show nothing if no sub-departments assigned
            }
        }
        // Priority 2: Check permissions for other roles
        elseif ($actor->cannot('view any user')) {
            if ($actor->can('view department user') || $actor->hasRole('Admin de pole')) {
                // Pole (Department) level visibility: users from same departments
                $departmentIds = $actor->departments->pluck('id')->toArray();
                if (!empty($departmentIds)) {
                    $usersQuery->whereHas('departments', function ($q) use ($departmentIds) {
                        $q->whereIn('departments.id', $departmentIds);
                    });
                } else {
                    $usersQuery->whereRaw('1 = 0'); // Show nothing if no departments assigned
                }
            } elseif ($actor->can('view service user') || $actor->hasAnyRole(['Admin de cellule', 'user'])) {
                // Service-level visibility: users from same services
                $serviceIds = $actor->services->pluck('id')->toArray();
                if (!empty($serviceIds)) {
                    $usersQuery->whereHas('services', function ($q) use ($serviceIds) {
                        $q->whereIn('services.id', $serviceIds);
                    });
                } else {
                    $usersQuery->whereRaw('1 = 0');
                }
            } elseif ($actor->can('view own user')) {
                // Show only the current user
                $usersQuery->where('id', $actor->id);
            } else {
                // No permission to view any users
                $usersQuery->whereRaw('1 = 0');
            }
        }
        // else: they have "view any user" permission - show all users (for Super Admin, Master, etc.)
        
        // Enforce role hierarchy visibility: only same-or-lower roles
        $viewer = auth()->user();
        $allowedRoleNames = RoleHierarchy::allowedRoleNamesFor($viewer);
        if (!empty($allowedRoleNames)) {
            // Show users only if ALL their roles are within allowed set
            // 1) They must have at least one allowed role
            $usersQuery->whereHas('roles', function ($q) use ($allowedRoleNames) {
                $q->whereIn('name', $allowedRoleNames);
            });
            // 2) And they must NOT have any role outside allowed set
            $usersQuery->whereDoesntHave('roles', function ($q) use ($allowedRoleNames) {
                $q->whereNotIn('name', $allowedRoleNames);
            });
        } else {
            // If current user has no recognized role, show none
            $usersQuery->whereRaw('1 = 0');
        }
        
        $users = $usersQuery
            ->when($this->search, fn($q) =>
            $q->where('full_name', 'like', '%' . $this->search . '%')
                ->orWhere('email','like', '%' . $this->search . '%')
            )
            ->when($this->role, fn($q) =>
            $q->whereHas('roles',function ($q) {
                $q->where('name','like', '%' . $this->role . '%');
            })
            )
            ->when($this->department, fn($q) =>
            $q->whereHas('departments',function ($q) {
                $q->where('departments.id',$this->department);
            })
            )
            ->when($this->dateFrom, fn($q) =>
            $q->whereDate('created_at', '>=', $this->dateFrom)
            )
            ->when($this->dateTo, fn($q) =>
            $q->whereDate('created_at', '<=', $this->dateTo)
            )
            ->latest()
            ->paginate(10);

        $this->usersIds = $users->pluck('id')->toArray();

        // Limit available role filter options by viewer's allowed roles
        $roles = Role::whereIn('name', $allowedRoleNames)->get();

        // Limit visible departments for filters and user modal, mirroring UserController@index
        $current = $viewer;
        $departmentsQuery = Department::withoutGlobalScopes()->with('subDepartments.services');

        if ($current->hasRole('master') || $current->hasRole('Super Administrator')) {
            $departments = $departmentsQuery->get();
        } else {
            // Base department IDs on explicit department assignments first
            $deptIds = $current->departments()->pluck('departments.id');

            // Gather services and sub-departments explicitly assigned
            $serviceIds = $current->services()->pluck('services.id');
            $explicitSubDeptIds = $current->subDepartments()->pluck('sub_departments.id');

            // Also infer sub-departments from assigned services
            $serviceSubDeptIds = $serviceIds->isNotEmpty()
                ? \DB::table('services')->whereIn('id', $serviceIds)->pluck('sub_department_id')
                : collect();

            $allSubDeptIds = $explicitSubDeptIds->merge($serviceSubDeptIds)->unique();

            // If no departments are directly assigned, infer them from sub-departments
            if ($deptIds->isEmpty() && $allSubDeptIds->isNotEmpty()) {
                $deptIds = \DB::table('sub_departments')
                    ->whereIn('id', $allSubDeptIds)
                    ->pluck('department_id');
            }

            // Treat "Admin de departments" the same as the English alias "Division Chief"
            $isDivisionChief = $current->hasAnyRole(['Admin de departments', 'Division Chief']);
            $isServiceManager = $current->hasAnyRole(['Admin de cellule', 'service manager']);

            if ($isDivisionChief || $isServiceManager) {
                // Division Chief & Service Manager: only own departments and own sub-departments
                $departments = $departmentsQuery
                    ->whereIn('id', $deptIds)
                    ->get()
                    ->map(function ($dept) use ($allSubDeptIds, $isServiceManager, $serviceIds) {
                        $subDepts = $dept->subDepartments
                            ->whereIn('id', $allSubDeptIds)
                            ->values();

                        // For service managers, also restrict services to those explicitly assigned
                        if ($isServiceManager && $serviceIds->isNotEmpty()) {
                            $subDepts->each(function ($subDept) use ($serviceIds) {
                                $subDept->setRelation(
                                    'services',
                                    $subDept->services->whereIn('id', $serviceIds)->values()
                                );
                            });
                        }

                        $dept->setRelation('subDepartments', $subDepts);
                        return $dept;
                    });
            } else {
                // Department Admin, Service User and others:
                // only own departments; keep all sub-departments/services under them
                $departments = $departmentsQuery
                    ->whereIn('id', $deptIds)
                    ->get();
            }
        }

        return view('livewire.users-table', compact('users','roles','departments'));
    }
}
