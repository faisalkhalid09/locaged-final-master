<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class DeletionLogsTable extends Component
{
    use WithPagination;

    // Filters
    public $search = '';
    public $creationDate = '';
    public $expirationDate = '';
    public $deletedAt = '';
    public $deletedBy = '';
    public $departmentId = '';
    public $perPage = 25;
    public $documentId = null; // Filter by specific document ID

    protected $queryString = [
        'search' => ['except' => ''],
        'creationDate' => ['except' => ''],
        'expirationDate' => ['except' => ''],
        'deletedAt' => ['except' => ''],
        'deletedBy' => ['except' => ''],
        'departmentId' => ['except' => ''],
        'perPage' => ['except' => 25],
        'documentId' => ['except' => null, 'as' => 'document_id'],
    ];

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->hasAnyRole(['master', 'Super Administrator', 'super administrator', 'Admin de pole', 'admin de pôle'])) {
            abort(403);
        }
    }

    public function updated($field)
    {
        if (in_array($field, [
            'search', 'creationDate', 'expirationDate',
            'deletedAt', 'deletedBy', 'departmentId', 'perPage', 'documentId'
        ])) {
            $this->resetPage();
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->creationDate = '';
        $this->expirationDate = '';
        $this->deletedAt = '';
        $this->deletedBy = '';
        $this->departmentId = '';
        $this->perPage = 25;
        $this->documentId = null;
        $this->resetPage();
    }

    private function buildQuery()
    {
        $current = auth()->user();
        $isDeptAdmin = $current && (
            $current->hasRole('Department Administrator') ||
            $current->hasRole('Admin de pole')
        );
        
        return AuditLog::with(['user', 'document' => function ($q) {
                $q->withTrashed()->with(['department', 'service.subDepartment']);
            }])
            ->where('action', 'permanently_deleted')
            // Department Administrator: only see logs from their department and users below their rank
            ->when($isDeptAdmin, function($q) use ($current) {
                $deptIds = $current->departments?->pluck('id') ?? collect();
                $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
                
                $q->where(function($subQuery) use ($deptIds, $allowedRoleNames) {
                    // Filter by document department
                    $subQuery->whereHas('document', function($q2) use ($deptIds) {
                        $q2->withTrashed()->whereIn('department_id', $deptIds);
                    })
                    // AND filter by user role (only users below admin's rank)
                    ->whereHas('user.roles', function($q2) use ($allowedRoleNames) {
                        $q2->whereIn('name', $allowedRoleNames);
                    });
                });
            })
            // Search by document title
            ->when($this->search, function($q) {
                $q->whereHas('document', function($q2) {
                    $q2->withTrashed()->where('title', 'like', '%' . $this->search . '%');
                });
            })
            // Filter by creation date
            ->when($this->creationDate, function($q) {
                $q->whereHas('document', function($q2) {
                    $q2->withTrashed()->whereDate('created_at', $this->creationDate);
                });
            })
            // Filter by expiration date
            ->when($this->expirationDate, function($q) {
                $q->whereHas('document', function($q2) {
                    $q2->withTrashed()->whereDate('expire_at', $this->expirationDate);
                });
            })
            // Filter by deleted at date
            ->when($this->deletedAt, function($q) {
                $q->whereDate('occurred_at', $this->deletedAt);
            })
            // Filter by user who deleted
            ->when($this->deletedBy, function($q) {
                $q->where('user_id', $this->deletedBy);
            })
            // Filter by department (structure)
            ->when($this->departmentId, function($q) {
                $q->whereHas('document', function($q2) {
                    $q2->withTrashed()->where('department_id', $this->departmentId);
                });
            })
            // Filter by specific document ID (from audit page)
            ->when($this->documentId, function($q) {
                $q->where('document_id', $this->documentId);
            })
            ->orderByDesc('occurred_at');
    }

    public function export()
    {
        $user = auth()->user();
        if (! $user || ! $user->hasAnyRole(['master', 'Super Administrator', 'super administrator', 'Admin de pole', 'admin de pôle'])) {
            abort(403);
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\DeletionLogsExport($this->buildQuery()->get()),
            'deletion-logs-' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function exportSinglePdf($logId)
    {
        $user = auth()->user();
        if (! $user || ! $user->hasAnyRole(['master', 'Super Administrator', 'super administrator', 'Admin de pole', 'admin de pôle'])) {
            abort(403);
        }

        // Set locale for translations in PDF
        app()->setLocale($user->locale ?? 'fr');

        $log = AuditLog::with(['user', 'document' => function ($q) {
                $q->withTrashed()->with(['department', 'service.subDepartment']);
            }])
            ->where('action', 'permanently_deleted')
            ->findOrFail($logId);

        $doc = $log->document;
        $dept = $doc?->department;
        $service = $doc?->service;
        $subDept = $service?->subDepartment;

        // Build structure string
        $structure = collect([$dept?->name, $subDept?->name, $service?->name])
            ->filter()
            ->implode(' > ');

        $html = view('pdf.deletion-log-single', [
            'log' => $log,
            'doc' => $doc,
            'structure' => $structure ?: '—',
        ])->render();

        // Configure mPDF for multi-language support (including Arabic/RTL)
        $isArabic = ($user->locale ?? 'fr') === 'ar';
        
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'default_font' => 'dejavusans', // Supports Arabic and special characters
            'directionality' => $isArabic ? 'rtl' : 'ltr', // RTL for Arabic
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);
        $mpdf->WriteHTML($html);

        return response()->streamDownload(
            fn () => print($mpdf->Output('', 'S')),
            'deletion-log-' . $log->id . '-' . now()->format('Ymd_His') . '.pdf'
        );
    }

    public function render()
    {
        $logs = $this->buildQuery()->paginate($this->perPage);

        // Get statistics (respect department scoping and role hierarchy)
        $current = auth()->user();
        $isDeptAdmin = $current && (
            $current->hasRole('Department Administrator') ||
            $current->hasRole('Admin de pole')
        );
        
        $statsBase = AuditLog::where('action', 'permanently_deleted');
        
        // Apply same filtering as main query for Department Admins
        if ($isDeptAdmin) {
            $deptIds = $current->departments?->pluck('id') ?? collect();
            $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
            
            $statsBase->where(function($subQuery) use ($deptIds, $allowedRoleNames) {
                // Filter by document department
                $subQuery->whereHas('document', function($q2) use ($deptIds) {
                    $q2->withTrashed()->whereIn('department_id', $deptIds);
                })
                // AND filter by user role (only users below admin's rank)
                ->whereHas('user.roles', function($q2) use ($allowedRoleNames) {
                    $q2->whereIn('name', $allowedRoleNames);
                });
            });
        }
        
        $totalDeleted = (clone $statsBase)->count();
        $thisWeekDeleted = (clone $statsBase)
            ->whereBetween('occurred_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
        $todayDeleted = (clone $statsBase)
            ->whereDate('occurred_at', today())
            ->count();

        // Get filter options (respect department scoping and role hierarchy)
        $current = auth()->user();
        $isDeptAdmin = $current && (
            $current->hasRole('Department Administrator') ||
            $current->hasRole('Admin de pole')
        );
        
        if ($isDeptAdmin) {
            $deptIds = $current->departments?->pluck('id') ?? collect();
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

        return view('livewire.deletion-logs-table', compact(
            'logs', 
            'totalDeleted', 
            'thisWeekDeleted', 
            'todayDeleted', 
            'users', 
            'departments'
        ));
    }
}
