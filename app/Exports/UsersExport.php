<?php

namespace App\Exports;

use App\Exports\Concerns\DefaultStyles;
use App\Models\User;
use App\Support\RoleHierarchy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class UsersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    use DefaultStyles;

    protected int $rowCount = 1;
    protected Request $request;

    /** @var array<string,int>|null */
    protected ?array $byRole = null;

    /** @var array<string,int>|null */
    protected ?array $byDepartment = null;

    protected ?int $totalUsers = null;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    protected function baseQuery(): Builder
    {
        $current = Auth::user();

        $allowedRoleNames = RoleHierarchy::allowedRoleNamesFor($current);

        $query = User::query()
            ->with(['roles', 'departments', 'services'])
            ->when(!empty($allowedRoleNames), function ($q) use ($allowedRoleNames) {
                $q->whereHas('roles', function ($qr) use ($allowedRoleNames) {
                    $qr->whereIn('name', $allowedRoleNames);
                });
            }, function ($q) {
                // If current user has no allowed roles (should not happen), hide all
                $q->whereRaw('1 = 0');
            });

        // Respect the same department visibility logic as in UserController@index
        if (! $current->hasRole('master') && ! $current->hasRole('Super Administrator')) {
            $deptIds = DB::table('department_user')
                ->where('user_id', $current->id)
                ->pluck('department_id');

            $serviceIds = DB::table('service_user')
                ->where('user_id', $current->id)
                ->pluck('service_id');

            $explicitSubDeptIds = DB::table('sub_department_user')
                ->where('user_id', $current->id)
                ->pluck('sub_department_id');

            $serviceSubDeptIds = $serviceIds->isNotEmpty()
                ? DB::table('services')->whereIn('id', $serviceIds)->pluck('sub_department_id')
                : collect();

            $allSubDeptIds = $explicitSubDeptIds->merge($serviceSubDeptIds)->unique();

            if ($deptIds->isEmpty() && $allSubDeptIds->isNotEmpty()) {
                $deptIds = DB::table('sub_departments')
                    ->whereIn('id', $allSubDeptIds)
                    ->pluck('department_id');
            }

            if ($deptIds->isNotEmpty()) {
                $query->whereHas('departments', function ($q) use ($deptIds) {
                    $q->whereIn('departments.id', $deptIds);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Filters for the export
        $r = $this->request;

        // Role filter
        if ($r->filled('role')) {
            $roleName = $r->role;
            $query->whereHas('roles', function ($q) use ($roleName) {
                $q->where('name', $roleName)->orWhere('id', $roleName);
            });
        }
        // Department filter
        if ($r->filled('department_id')) {
            $deptId = $r->department_id;
            $query->whereHas('departments', function ($q) use ($deptId) {
                $q->where('departments.id', $deptId);
            });
        }

        // Creation date range
        if ($r->filled('created_from')) {
            $query->whereDate('users.created_at', '>=', $r->created_from);
        }
        if ($r->filled('created_to')) {
            $query->whereDate('users.created_at', '<=', $r->created_to);
        }

        // Status
        if ($r->filled('status') && $r->status !== 'all') {
            $query->where('active', $r->status === 'active');
        }

        return $query;
    }

    public function query(): Builder
    {
        return $this->baseQuery()->orderBy('id');
    }

    public function headings(): array
    {
        return [
            __('Name'),
            __('Email'),
            __('Role(s)'),
            __('Structure(s)'),
            __('Service(s)'),
            __('Creation date'),
            __('Last login'),
            __('Status'),
        ];
    }

    public function map($user): array
    {
        $this->rowCount++;

        $roles = $user->roles->pluck('name')->join(', ') ?: __('N/A');
        $departments = $user->departments->pluck('name')->join(', ') ?: __('N/A');
        $services = $user->services->pluck('name')->join(', ') ?: __('N/A');

        // Approximate last login using sessions table (max last_activity)
        $lastLogin = DB::table('sessions')
            ->where('user_id', $user->id)
            ->max('last_activity');
        $lastLoginFormatted = $lastLogin
            ? date('d/m/Y H:i', $lastLogin)
            : __('N/A');

        return [
            $user->full_name,
            $user->email,
            $roles,
            $departments,
            $services,
            optional($user->created_at)->format('d/m/Y H:i'),
            $lastLoginFormatted,
            $user->active ? __('Active') : __('Deactivated'),
        ];
    }

    protected function ensureStatsLoaded(): void
    {
        if ($this->byRole !== null) {
            return;
        }

        $users = $this->baseQuery()->get();
        $this->totalUsers = $users->count();

        $this->byRole = $users->groupBy(function ($user) {
            return $user->roles->pluck('name')->first() ?? __('No role');
        })->map->count()->toArray();

        $this->byDepartment = $users->groupBy(function ($user) {
            return $user->departments->pluck('name')->first() ?? __('No structure');
        })->map->count()->toArray();
    }

    protected function buildFiltersSummary(): string
    {
        $r = $this->request;
        $parts = [];

        if ($r->filled('role')) {
            $parts[] = 'Rôle=' . $r->role;
        }
        if ($r->filled('department_id')) {
            $parts[] = 'Structure ID=' . $r->department_id;
        }
        if ($r->filled('created_from') || $r->filled('created_to')) {
            $from = $r->created_from ? date('d/m/Y', strtotime($r->created_from)) : '...';
            $to = $r->created_to ? date('d/m/Y', strtotime($r->created_to)) : '...';
            $parts[] = "Créé entre={$from} → {$to}";
        }
        if ($r->filled('status') && $r->status !== 'all') {
            $parts[] = 'Statut=' . ($r->status === 'active' ? 'Actif' : 'Désactivé');
        }

        return $parts ? implode(' ; ', $parts) : __('No filters (all users)');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->ensureStatsLoaded();

                $lastRow = $this->rowCount;
                $lastCol = 'H';
                $this->applyDefaultSheetStyles($event, $lastRow, $lastCol);
                $sheet = $event->sheet->getDelegate();
                $sheetTitle = $sheet->getTitle();

                // Insert header rows above table
                $sheet->insertNewRowBefore(1, 5);

                $sheet->setCellValue('A1', __('Users Report'));
                $sheet->mergeCells('A1:H1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

                $sheet->setCellValue('A2', __('Generated on:') . ' ' . now()->format('d/m/Y H:i'));
                $sheet->setCellValue('A3', __('Active filters:') . ' ' . $this->buildFiltersSummary());

                $total = $this->totalUsers ?? ($this->rowCount - 1);
                $sheet->setCellValue('A4', __('Total users:') . ' ' . $total);

                // Freeze data header row (now at 6)
                $sheet->freezePane('A6');
            },
        ];
    }
}
