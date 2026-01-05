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
        
        $isDeAdmin = $current && (
            $current->hasRole('Department Administrator') ||
            $current->hasRole('Admin de pole') ||
            $current->hasRole('Admin de departments')
        );

        $isServiceManager = $current && (
            $current->hasRole('Admin de cellule') ||
            $current->hasRole('Service Manager')
        );
        
        return \App\Models\AuthenticationLog::with('user')
            // Department Administrator: only see logs from their department and users below their rank
            ->when($isDeAdmin && !$current->hasRole('master') && !$current->hasRole('super administrator'), function($q) use ($current) {
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
            // Service Manager: only see logs from their services and users below their rank OR their own logs
            ->when($isServiceManager && !$isDeAdmin && !$current->hasRole('master') && !$current->hasRole('super administrator'), function($q) use ($current) {
                $serviceIds = $this->getAccessibleServiceIds($current);
                $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);

                $q->whereHas('user', function($q2) use ($serviceIds, $allowedRoleNames, $current) {
                     $q2->where(function($query) use ($serviceIds, $allowedRoleNames, $current) {
                        // Case A: Subordinate User in Manager's Service
                        $query->where(function($subQ) use ($serviceIds, $allowedRoleNames) {
                             $subQ->where(function($sQ) use ($serviceIds) {
                                     // Check Service Assignment (Direct or Pivot)
                                     $sQ->whereIn('users.service_id', $serviceIds)
                                        ->orWhereHas('services', function($pivot) use ($serviceIds) {
                                            $pivot->whereIn('services.id', $serviceIds);
                                        });
                             })
                             // Check Rank (Strictly Lower)
                             ->whereHas('roles', function($r) use ($allowedRoleNames) {
                                 $r->whereIn('name', $allowedRoleNames);
                             });
                        })
                        // Case B: The Service Manager Themselves
                        ->orWhere('users.id', $current->id);
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

    private function getAccessibleServiceIds(User $user)
    {
        // Matches the fixed logic in HomeController and Document model
        $serviceIds = collect();

        // 1. Direct service assignment
        if ($user->service_id) {
            $serviceIds->push($user->service_id);
        }

        // 2. Many-to-many service assignments via pivot
        if ($user->relationLoaded('services') || method_exists($user, 'services')) {
            $serviceIds = $serviceIds->merge($user->services->pluck('id'));
        }

        // For non-service-level users (like Dept Admins), we would include sub-department services
        // But here we are specifically targeting Service Managers who only see DIRECT services.
        
        return $serviceIds->unique()->filter();
    }

    private function buildDocumentQuery()
    {
        $current = auth()->user();

        $query = AuditLog::with([
                'user.departments', 
                'document' => function($q) {
                    // Include soft-deleted documents so audit logs show the document name
                    $q->withTrashed();
                },
                'document.department', 
                'documentVersion'
            ])
            // Exclude OCR view activity from logs
            ->where('action', '!=', 'viewed_ocr');

        $isDeptAdmin = $current && (
            $current->hasRole('Department Administrator') ||
            $current->hasRole('Admin de pole') ||
            $current->hasRole('Admin de departments')
        );

        $isServiceManager = $current && (
            $current->hasRole('Admin de cellule') ||
            $current->hasRole('Service Manager')
        );

        // Department Admin Scope
        $query->when($isDeptAdmin && !$current->hasRole('master') && !$current->hasRole('super administrator'), function($q) use ($current) {
            $deptIds = $current->departments?->pluck('id') ?? collect();
            $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
            
            if ($deptIds->isNotEmpty()) {
                $q->where(function($q2) use ($deptIds) {
                    // Document belongs to one of the admin's departments
                    $q2->whereHas('document', function($q3) use ($deptIds) {
                        $q3->withTrashed()->whereIn('documents.department_id', $deptIds);
                    });
                })
                // AND user must be subordinate
                ->whereHas('user.roles', function($q2) use ($allowedRoleNames) {
                    $q2->whereIn('name', $allowedRoleNames);
                });
            } else {
                $q->whereRaw('1 = 0');
            }
        });

        // Service Manager Scope
        $query->when($isServiceManager && !$isDeptAdmin && !$current->hasRole('master') && !$current->hasRole('super administrator'), function($q) use ($current) {
            $serviceIds = $this->getAccessibleServiceIds($current);
            $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);

            if ($serviceIds->isNotEmpty()) {
                // Constraint 1: Document MUST be in one of the manager's services
                $q->whereHas('document', function($d) use ($serviceIds) {
                    $d->withTrashed()->whereIn('documents.service_id', $serviceIds);
                })
                // Constraint 2: Actor MUST be (Subordinate OR Myself)
                ->where(function($u) use ($allowedRoleNames, $current) {
                     $u->where('user_id', $current->id)
                       ->orWhereHas('user.roles', function($r) use ($allowedRoleNames) {
                           $r->whereIn('name', $allowedRoleNames);
                       });
                });
            } else {
                 // No services assigned -> See specific 'own' actions? 
                 // User said "also related to his service". If no service, then satisfy nothing usually.
                 // But typically if I have NO service, strictly speaking "related to his service" is Empty Set.
                 // However, for safety, if I am truly unassigned, I probably shouldn't see doc logs except maybe entirely private ones?
                 // Given the instructions "No other case should be satisfied", 1=0 is safest if no service.
                 $q->whereRaw('1 = 0');
            }
        });

        return $query->when($this->dateFrom, function($q) {
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

        // Get statistics for cards (respect same department/service scoping and role filtering)
        $current = auth()->user();
        $deptIds = $current && $current->departments ? $current->departments->pluck('id') : collect();
        $isSuper = $current && $current->hasRole(['master','super administrator','super_admin']);
        
        $isDeptAdmin = $current && (
            $current->hasRole('Department Administrator') ||
            $current->hasRole('Admin de pole') ||
            $current->hasRole('Admin de departments')
        );

        $isServiceManager = $current && (
            $current->hasRole('Admin de cellule') ||
            $current->hasRole('Service Manager')
        );

        $statsBase = AuditLog::query()
            ->where('action', '!=', 'viewed_ocr')
            // Department Admin: filter by department
            ->when($isDeptAdmin && $deptIds->isNotEmpty() && ! $isSuper, function($q) use ($deptIds, $current) {
                $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
                
                $q->whereHas('document', function($q2) use ($deptIds) {
                    $q2->withTrashed()->whereIn('department_id', $deptIds);
                })
                ->whereHas('user.roles', function($q2) use ($allowedRoleNames) {
                    $q2->whereIn('name', $allowedRoleNames);
                });
            })
            // Service Manager: filter by service
            ->when($isServiceManager && !$isDeptAdmin && ! $isSuper, function($q) use ($current) {
                $serviceIds = $this->getAccessibleServiceIds($current);
                $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
                
                if ($serviceIds->isNotEmpty()) {
                    $q->whereHas('document', function($q2) use ($serviceIds) {
                        $q2->withTrashed()->whereIn('service_id', $serviceIds);
                    })
                    ->whereHas('user.roles', function($q2) use ($allowedRoleNames) {
                        $q2->whereIn('name', $allowedRoleNames);
                    });
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

        $totalLogs = (clone $statsBase)->count();
        $todayLogs = (clone $statsBase)->whereDate('occurred_at', today())->count();
        $thisWeekLogs = (clone $statsBase)->whereBetween('occurred_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $uniqueUsers = (clone $statsBase)->distinct('user_id')->count('user_id');

        // Get filter options (respect department/service scoping and role hierarchy)
        if ($isDeptAdmin && $deptIds->isNotEmpty() && ! $isSuper) {
            $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
            
            $users = User::whereHas('departments', function($q) use ($deptIds) {
                    $q->whereIn('departments.id', $deptIds);
                })
                ->whereHas('roles', function($q) use ($allowedRoleNames) {
                    $q->whereIn('name', $allowedRoleNames);
                })
                ->orderBy('full_name')
                ->get();
            $departments = Department::whereIn('id', $deptIds)->orderBy('name')->get();
        } elseif ($isServiceManager && !$isDeptAdmin && ! $isSuper) {
            $serviceIds = $this->getAccessibleServiceIds($current);
            $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
            
            if ($serviceIds->isNotEmpty()) {
                $users = User::where(function($q) use ($serviceIds) {
                        $q->whereIn('service_id', $serviceIds)
                          ->orWhereHas('services', function($sq) use ($serviceIds) {
                              $sq->whereIn('services.id', $serviceIds);
                          });
                    })
                    ->whereHas('roles', function($q) use ($allowedRoleNames) {
                        $q->whereIn('name', $allowedRoleNames);
                    })
                    ->orderBy('full_name')
                    ->get();
            } else {
                $users = collect();
            }
            $departments = collect();
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

