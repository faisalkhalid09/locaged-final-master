<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogsTable extends Component
{
    use WithPagination;

    public $search = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $userId = '';
    public $departmentId = '';
    public $actionType = '';
    public $perPage = 25;

    public $logType = 'documents'; // 'documents' or 'authentication'

    protected $queryString = [
        'search' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'userId' => ['except' => ''],
        'departmentId' => ['except' => ''],
        'actionType' => ['except' => ''],
        'perPage' => ['except' => 25],
        'logType' => ['except' => 'documents'],
    ];

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    public function updated($field)
    {
        if (in_array($field, ['search', 'dateFrom', 'dateTo', 'userId', 'departmentId', 'actionType', 'perPage', 'logType'])) {
            $this->resetPage();
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->userId = '';
        $this->departmentId = '';
        $this->actionType = '';
        $this->perPage = 25;
        $this->logType = 'documents';
        $this->resetPage();
    }

    public function setLogType($type)
    {
        $this->logType = $type;
        $this->resetPage();
    }

    public function export()
    {
        Gate::authorize('viewAny', User::class);
        
        $query = $this->buildQuery();
        $logs = $query->get();

        $filename = 'activity_logs_' . $this->logType . '_' . now()->format('Y-m-d_His') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ActivityLogsExport($logs, $this->logType),
            $filename
        );
    }

    private function buildQuery()
    {
        if ($this->logType === 'authentication') {
            return $this->buildAuthenticationQuery();
        }
        return $this->buildDocumentQuery();
    }

    private function buildAuthenticationQuery()
    {
        $current = auth()->user();
        $isDeptAdmin = $current && (
            $current->hasRole('Department Administrator') ||
            $current->hasRole('Admin de pole')
        );
        
        return \App\Models\AuthenticationLog::with('user')
            // Department Administrator: only see logs from their department and users below their rank
            ->when($isDeptAdmin, function($q) use ($current) {
                $deptIds = $current->departments?->pluck('id') ?? collect();
                $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
                
                $q->whereHas('user', function($q2) use ($deptIds, $allowedRoleNames) {
                    // User must be in one of the admin's departments
                    $q2->whereHas('departments', function($q3) use ($deptIds) {
                        $q3->whereIn('departments.id', $deptIds);
                    })
                    // AND user must have a role below the admin's rank
                    ->whereHas('roles', function($q3) use ($allowedRoleNames) {
                        $q3->whereIn('name', $allowedRoleNames);
                    });
                });
            })
            ->when($this->dateFrom, function($q) {
                $q->whereDate('occurred_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function($q) {
                $q->whereDate('occurred_at', '<=', $this->dateTo);
            })
            ->when($this->userId, function($q) {
                $q->where('user_id', $this->userId);
            })
            ->when($this->actionType, function($q) {
                $q->where('type', $this->actionType);
            })
            ->when($this->search, function($q) {
                $q->where(function($q2) {
                    $q2->where('email', 'like', '%' . $this->search . '%')
                       ->orWhere('ip_address', 'like', '%' . $this->search . '%')
                       ->orWhereHas('user', function($q3) {
                           $q3->where('full_name', 'like', '%' . $this->search . '%');
                       });
                });
            })
            ->latest('occurred_at');
    }

    private function buildDocumentQuery()
    {
        $current = auth()->user();

        $query = AuditLog::with(['user.departments', 'document.department', 'documentVersion'])
            // Exclude OCR view activity from logs
            ->where('action', '!=', 'viewed_ocr')
            // Scope to current user's departments for department-level roles only
            ->when($current && (
                $current->hasRole('Department Administrator') ||
                $current->hasRole('Admin de pole') ||
                $current->hasRole('Admin de departments') ||
                $current->hasRole('Admin de cellule') ||
                $current->hasRole('user')
            ), function($q) use ($current) {
                $deptIds = $current->departments?->pluck('id') ?? collect();
                $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
                
                if ($deptIds->isNotEmpty()) {
                    $q->whereHas('document', function($q2) use ($deptIds) {
                        $q2->whereIn('department_id', $deptIds);
                    })
                    // CRITICAL: Also filter by user role - only show logs from subordinate users
                    ->whereHas('user.roles', function($q2) use ($allowedRoleNames) {
                        $q2->whereIn('name', $allowedRoleNames);
                    });
                } else {
                    // If they have no departments assigned, they should see nothing
                    $q->whereRaw('1 = 0');
                }
            })
            ->when($this->dateFrom, function($q) {
                $q->whereDate('occurred_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function($q) {
                $q->whereDate('occurred_at', '<=', $this->dateTo);
            })
            ->when($this->userId, function($q) {
                $q->where('user_id', $this->userId);
            })
            ->when($this->departmentId, function($q) {
                $q->whereHas('document', function($q2) {
                    $q2->where('department_id', $this->departmentId);
                });
            })
            ->when($this->actionType, function($q) {
                $q->where('action', $this->actionType);
            })
            ->when($this->search, function($q) {
                $q->where(function($q2) {
                    $q2->whereHas('user', function($q3) {
                        $q3->where('full_name', 'like', '%' . $this->search . '%')
                           ->orWhere('email', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('document', function($q3) {
                        $q3->where('title', 'like', '%' . $this->search . '%');
                    })
                    ->orWhere('action', 'like', '%' . $this->search . '%');
                });
            })
            ->latest('occurred_at');

        return $query;
    }

    private function getResult($log): string
    {
        // Determine result based on action type
        $successActions = ['created', 'approved', 'updated', 'downloaded', 'viewed', 'archived', 'renamed', 'locked', 'unlocked', 'moved', 'viewed_ocr'];
        $failureActions = ['declined', 'failed_access'];
        $deleteActions = ['permanently_deleted', 'destroyed'];
        
        if (in_array($log->action, $successActions)) {
            return 'Success';
        } elseif (in_array($log->action, $failureActions)) {
            return 'Failed';
        } elseif (in_array($log->action, $deleteActions)) {
            return 'Deleted';
        }
        
        return 'Completed';
    }

    public function render()
    {
        $query = $this->buildQuery();
        $logs = $query->paginate($this->perPage);

        // Get statistics for cards (respect same department scoping)
        $current = auth()->user();
        $deptIds = $current && $current->departments ? $current->departments->pluck('id') : collect();
        $isSuper = $current && $current->hasRole(['master','super administrator','super_admin']);
        $isDeptScopedRole = $current && (
            $current->hasRole('Department Administrator') ||
            $current->hasRole('Admin de pole') ||
            $current->hasRole('Admin de departments') ||
            $current->hasRole('Admin de cellule') ||
            $current->hasRole('user')
        );

        $statsBase = AuditLog::query()
            ->when($current && $isDeptScopedRole && $deptIds->isNotEmpty() && ! $isSuper, function($q) use ($deptIds) {
                $q->whereHas('document', function($q2) use ($deptIds) {
                    $q2->whereIn('department_id', $deptIds);
                });
            });

        $totalLogs = (clone $statsBase)->count();
        $todayLogs = (clone $statsBase)->whereDate('occurred_at', today())->count();
        $thisWeekLogs = (clone $statsBase)->whereBetween('occurred_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $uniqueUsers = (clone $statsBase)->distinct('user_id')->count('user_id');

        // Get filter options (respect department scoping and role hierarchy)
        if ($current && $isDeptScopedRole && $deptIds->isNotEmpty() && ! $isSuper) {
            $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
            
            $users = User::whereHas('departments', function($q) use ($deptIds) {
                    $q->whereIn('departments.id', $deptIds);
                })
                // Only show users with roles below current user's rank
                ->whereHas('roles', function($q) use ($allowedRoleNames) {
                    $q->whereIn('name', $allowedRoleNames);
                })
                ->orderBy('full_name')
                ->get();
            $departments = Department::whereIn('id', $deptIds)->orderBy('name')->get();
        } else {
            $users = User::orderBy('full_name')->get();
            $departments = Department::orderBy('name')->get();
        }
        
        // Get unique action types
        if ($this->logType === 'authentication') {
            $actionTypes = ['login_success', 'login_failed', 'logout'];
        } else {
            $actionTypes = AuditLog::distinct()->pluck('action')->sort()->values();
        }

        return view('livewire.activity-logs-table', compact('logs', 'totalLogs', 'todayLogs', 'thisWeekLogs', 'uniqueUsers', 'users', 'departments', 'actionTypes'));
    }
}

