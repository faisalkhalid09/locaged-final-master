<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\Service;
use App\Models\SubDepartment;
use App\Models\Subcategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    public function index()
    {
        // Base query for all dashboard statistics: only documents the current user can actually see
        $visibleDocumentsQuery = $this->getVisibleDocumentsQuery();

        // Paginated list of visible documents
        $documents = (clone $visibleDocumentsQuery)->paginate(10);
        $totalDocuments = $documents->total();
        $categories = $this->getCategories();
        $statusSummary = $this->getStatusSummary();

        $weeklyData = $this->getWeeklyData();
        $monthlyData = $this->getMonthlyData();
        $yearlyData = $this->getYearlyData();

        // File type distribution (by actual file extension of latest version)
        // This can be very expensive on large datasets, so we skip it when there are too many documents.
        $documentTypeStats = [
            'labels' => [],
            'values' => [],
            'percents' => [],
            'colors' => [],
        ];

        $maxDocsForTypeStats = 5000; // safety threshold to avoid timeouts
        if ($totalDocuments > 0 && $totalDocuments <= $maxDocsForTypeStats) {
            $extCounts = [];
            $allDocs = (clone $visibleDocumentsQuery)->with('latestVersion')->get();
            foreach ($allDocs as $doc) {
                $path = $doc->latestVersion?->file_path;
                $ext = $path ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : 'unknown';
                $ext = $ext ?: 'unknown';
                $extCounts[$ext] = ($extCounts[$ext] ?? 0) + 1;
            }

            if (! empty($extCounts)) {
                ksort($extCounts);
                $typeTotal = max(1, array_sum($extCounts));
                $labels = array_keys($extCounts);
                $values = array_values($extCounts);
                $percents = array_map(fn($c) => round(($c / $typeTotal) * 100, 1), $extCounts);

                // Provide a color palette long enough for many types
                $palette = [
                    '#2563eb','#3b82f6','#60a5fa','#6366f1','#8b5cf6','#a855f7','#22c55e','#10b981','#14b8a6',
                    '#06b6d4','#0ea5e9','#f59e0b','#ef4444','#84cc16','#e11d48','#f97316','#475569','#0ea5a5'
                ];
                $colors = [];
                for ($i = 0; $i < count($labels); $i++) {
                    $colors[] = $palette[$i % count($palette)];
                }

                $documentTypeStats = [
                    'labels' => $labels,
                    'values' => $values,
                    'percents' => $percents,
                    'colors' => $colors,
                ];
            }
        }

        // Determine what to show in donut chart based on user's department access
        $user = auth()->user();
        
        // For master/super admin, populate with all departments
        $isMaster = $user && $user->hasRole('master');
        $isSuperAdmin = $user && ($user->hasRole('Super Administrator') || $user->hasRole('super administrator') || $user->hasRole('super_admin'));
        
        if ($isMaster || $isSuperAdmin || $user->can('view any department')) {
            $userDepartments = Department::all();
        } else {
            $userDepartments = $user->departments ?? collect();
        }
        
        $donutChartData = $this->getDonutChartData($userDepartments);

        // Rooms occupancy (by room) – count only documents the user can see
        // Filter rooms to only show those with boxes the user has access to
        $user = auth()->user();
        $allRooms = \App\Models\Room::orderBy('name')->get();
        $roomToCount = [];
        
        foreach ($allRooms as $room) {
            // Get boxes in this room that the user has access to (filtered by service)
            $boxIds = \App\Models\Box::forUser($user)
                ->whereHas('shelf.row.room', function ($q) use ($room) {
                    $q->where('id', $room->id);
                })
                ->pluck('id');

            // Only include this room if the user has access to at least one box in it
            // (Admin/SuperAdmin will see all rooms because forUser returns all boxes for them)
            if ($boxIds->isNotEmpty()) {
                $count = (clone $visibleDocumentsQuery)
                    ->whereIn('box_id', $boxIds)
                    ->count();

                $roomToCount[$room->name] = $count;
            }
        }

        $roomCards = collect($roomToCount)->map(function ($count, $room) {
            return [
                'room' => $room,
                'count' => $count,
            ];
        })->values();

        return view('home.index', compact(
            'totalDocuments', 'documents', 'categories',
            'weeklyData', 'monthlyData', 'yearlyData', 'statusSummary',
            'documentTypeStats', 'roomCards', 'donutChartData'
        ));
    }

    public function notifications()
    {
        $notifications = auth()->user()->unreadNotifications()->paginate(10);
        return view('home.notifications', compact('notifications'));
    }

    /**
     * Storage and server space overview for master & Super Administrator roles.
     */
    public function storageOverview()
    {
        $basePath = base_path();

        // Overall disk stats for the volume hosting the application
        $totalDiskBytes = @disk_total_space($basePath) ?: null;
        $freeDiskBytes  = $totalDiskBytes ? (@disk_free_space($basePath) ?: null) : null;

        $usedDiskBytes = null;
        $usedPercent   = null;
        $freePercent   = null;

        if ($totalDiskBytes !== null && $freeDiskBytes !== null && $totalDiskBytes > 0) {
            $usedDiskBytes = max(0, $totalDiskBytes - $freeDiskBytes);
            $usedPercent   = round(($usedDiskBytes / $totalDiskBytes) * 100, 1);
            $freePercent   = round(100 - $usedPercent, 1);
        }

        // Space used by application documents (local storage disk)
        $appBytes = 0;
        try {
            $localDisk = Storage::disk('local');
            foreach ($localDisk->allFiles() as $path) {
                try {
                    $appBytes += $localDisk->size($path);
                } catch (\Throwable $e) {
                    // Ignore files we cannot stat
                }
            }
        } catch (\Throwable $e) {
            // If storage disk is misconfigured, keep appBytes at 0 and show gracefully
            $appBytes = 0;
        }

        $appPercentOfDisk = null;
        $appPercentOfUsed = null;
        if ($totalDiskBytes && $totalDiskBytes > 0) {
            $appPercentOfDisk = round(($appBytes / $totalDiskBytes) * 100, 2);
        }
        if ($usedDiskBytes && $usedDiskBytes > 0) {
            $appPercentOfUsed = round(($appBytes / $usedDiskBytes) * 100, 2);
        }

        $warningThreshold  = 80; // % of total disk used
        $criticalThreshold = 90; // % of total disk used

        $disk = [
            'total_bytes'   => $totalDiskBytes,
            'used_bytes'    => $usedDiskBytes,
            'free_bytes'    => $freeDiskBytes,
            'used_percent'  => $usedPercent,
            'free_percent'  => $freePercent,
            'total_human'   => $totalDiskBytes !== null ? $this->formatBytes($totalDiskBytes) : null,
            'used_human'    => $usedDiskBytes !== null ? $this->formatBytes($usedDiskBytes) : null,
            'free_human'    => $freeDiskBytes !== null ? $this->formatBytes($freeDiskBytes) : null,
        ];

        $appStorage = [
            'bytes'             => $appBytes,
            'human'             => $this->formatBytes($appBytes),
            'percent_of_disk'   => $appPercentOfDisk,
            'percent_of_used'   => $appPercentOfUsed,
        ];

        $thresholds = [
            'warning'  => $warningThreshold,
            'critical' => $criticalThreshold,
        ];

        return view('home.storage', compact('disk', 'appStorage', 'thresholds'));
    }

    public function toggleRtl(Request $request)
    {
        session(['rtl' => $request->boolean('rtl')]);
        return back()->with('success','Content Direction changed successfully');
    }

    private function getCategories()
    {
        // Category cards are already permission-aware via policies on underlying pages.
        // We keep them unfiltered here so pagination and counts work as before.
        return Category::withCount([
            'documents as pending_count' => fn($q) => $q->where('status', 'pending'),
            'documents as total_count',
        ])->paginate(4);
    }

    /**
     * Base query for documents visible to the authenticated user, mirroring DocumentPolicy::view rules.
     */
    private function getVisibleDocumentsQuery(): Builder
    {
        $user = auth()->user();

        $query = Document::query();

        if (! $user) {
            // No authenticated user – return empty result set
            return $query->whereRaw('1 = 0');
        }

        if ($user->can('view any document')) {
            return $query;
        }

        // Special strict rule for Division Chief (must match department + service pair)
        if ($user->hasRole('Division Chief')) {
            $userDeptIds = ($user->relationLoaded('departments') || method_exists($user, 'departments'))
                ? $user->departments->pluck('id')->filter()
                : collect();

            $userSubDeptIds = collect();
            if ($user->relationLoaded('subDepartments') || method_exists($user, 'subDepartments')) {
                $userSubDeptIds = $userSubDeptIds->merge($user->subDepartments->pluck('id'));
            }
            $userSubDeptIds = $userSubDeptIds->unique()->filter();

            if ($userDeptIds->isEmpty() || $userSubDeptIds->isEmpty()) {
                return $query->whereRaw('1 = 0');
            }

            $allowedSubDeptIds = SubDepartment::whereIn('id', $userSubDeptIds)
                ->whereIn('department_id', $userDeptIds)
                ->pluck('id');

            if ($allowedSubDeptIds->isEmpty()) {
                return $query->whereRaw('1 = 0');
            }

            $serviceIds = Service::whereIn('sub_department_id', $allowedSubDeptIds)->pluck('id');
            if ($serviceIds->isEmpty()) {
                return $query->whereRaw('1 = 0');
            }

            return $query
                ->whereIn('department_id', $userDeptIds)
                ->whereIn('service_id', $serviceIds);
        }

        // Generic visibility rules (service, department, own documents)
        return $query->where(function (Builder $q) use ($user) {
            $hasCondition = false;

            if ($user->can('view service document')) {
                $visibleServiceIds = collect();

                if ($user->relationLoaded('services') || method_exists($user, 'services')) {
                    $visibleServiceIds = $visibleServiceIds->merge($user->services->pluck('id'));
                }

                $subDeptIds = collect();
                if ($user->relationLoaded('subDepartments') || method_exists($user, 'subDepartments')) {
                    $subDeptIds = $subDeptIds->merge($user->subDepartments->pluck('id'));
                }
                $subDeptIds = $subDeptIds->unique()->filter();

                if ($subDeptIds->isNotEmpty()) {
                    $visibleServiceIds = $visibleServiceIds->merge(
                        Service::whereIn('sub_department_id', $subDeptIds)->pluck('id')
                    );
                }

                $visibleServiceIds = $visibleServiceIds->unique()->filter();

                if ($visibleServiceIds->isNotEmpty()) {
                    $q->whereIn('service_id', $visibleServiceIds);
                    $hasCondition = true;
                }
            }

            if ($user->can('view department document')) {
                $deptIds = $user->departments->pluck('id')->filter();
                if ($deptIds->isNotEmpty()) {
                    $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                    $q->$method('department_id', $deptIds);
                    $hasCondition = true;
                }
            }

            if ($user->can('view own document')) {
                $method = $hasCondition ? 'orWhere' : 'where';
                $q->$method('created_by', $user->id);
                $hasCondition = true;
            }

            if (! $hasCondition) {
                // If user has no view permissions at all, force empty set
                $q->whereRaw('1 = 0');
            }
        });
    }

    private function getDonutChartData($userDepartments)
    {
        $user = auth()->user();
        $visibleDocumentsQuery = $this->getVisibleDocumentsQuery();
        $accessibleServiceIds = $this->getAccessibleServiceIds();

        $isMaster = $user && $user->hasRole('master');
        $isSuperAdmin = $user && ($user->hasRole('Super Administrator') || $user->hasRole('super administrator') || $user->hasRole('super_admin'));

        // Service-level roles:
        // - "Admin de cellule" is the canonical service manager role
        // - "user" is the service-level user role
        // Keep backward-compatibility with any legacy "Service Manager" / "Service User" roles.
        $isServiceManager = $user && ($user->hasRole('Admin de cellule') || $user->hasRole('Service Manager'));
        $isServiceUser = $user && ($user->hasRole('user') || $user->hasRole('Service User'));

        $isGlobalAdmin = $isMaster || $isSuperAdmin;
        $isServiceScoped = $isServiceManager || $isServiceUser;

        // ------------------------------------------------------------------
        // 1) Service Manager / Service User → start from their Services
        // ------------------------------------------------------------------
        if ($isServiceScoped && $accessibleServiceIds->isNotEmpty()) {
            $services = Service::whereIn('id', $accessibleServiceIds)
                ->orderBy('name')
                ->get()
                ->map(function ($service) use ($visibleDocumentsQuery) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'count' => (clone $visibleDocumentsQuery)->where('service_id', $service->id)->count(),
                    ];
                })
                ->filter(fn ($s) => $s['count'] > 0)
                ->values();

            if ($services->isEmpty()) {
                return [
                    'type' => 'services',
                    'data' => [],
                    'title' => ui_t('pages.dashboard.donut.services_title'),
                    'subtitle' => ui_t('pages.dashboard.donut.services_empty'),
                ];
            }

            return [
                'type' => 'services',
                'data' => $services,
                'title' => ui_t('pages.dashboard.donut.services_title'),
                'subtitle' => ui_t('pages.dashboard.donut.services_click'),
            ];
        }

        // ------------------------------------------------------------------
        // 2) All other users → start from Departments
        // ------------------------------------------------------------------
        $visibleDepartmentIds = collect();

        // Get departments from direct assignment
        if (method_exists($user, 'departments')) {
            $visibleDepartmentIds = $visibleDepartmentIds->merge($user->departments->pluck('id'));
        }

        // Get departments from sub-departments
        if (method_exists($user, 'subDepartments')) {
            $subDeptIds = $user->subDepartments->pluck('id');
            if ($subDeptIds->isNotEmpty()) {
                $deptIdsFromSubDepts = SubDepartment::whereIn('id', $subDeptIds)->pluck('department_id');
                $visibleDepartmentIds = $visibleDepartmentIds->merge($deptIdsFromSubDepts);
            }
        }

        // For global admins, show all departments
        if ($isGlobalAdmin || $user->can('view any department')) {
            $visibleDepartmentIds = Department::pluck('id');
        }

        $visibleDepartmentIds = $visibleDepartmentIds->unique()->filter();

        if ($visibleDepartmentIds->isEmpty()) {
            return [
                'type' => 'departments',
                'data' => [],
                'title' => ui_t('pages.dashboard.donut.departments_title'),
                'subtitle' => ui_t('pages.dashboard.donut.no_data'),
            ];
        }

        $departments = Department::whereIn('id', $visibleDepartmentIds)
            ->orderBy('name')
            ->get()
            ->map(function ($dept) use ($visibleDocumentsQuery) {
                return [
                    'id' => $dept->id,
                    'name' => $dept->name,
                    'count' => (clone $visibleDocumentsQuery)->where('department_id', $dept->id)->count(),
                ];
            })
            ->filter(fn ($d) => $d['count'] > 0)
            ->values();

        if ($departments->isEmpty()) {
            return [
                'type' => 'departments',
                'data' => [],
                'title' => ui_t('pages.dashboard.donut.departments_title'),
                'subtitle' => ui_t('pages.dashboard.donut.no_documents'),
            ];
        }

        return [
            'type' => 'departments',
            'data' => $departments,
            'title' => ui_t('pages.dashboard.donut.departments_title'),
            'subtitle' => ui_t('pages.dashboard.donut.departments_click'),
        ];
    }

    public function getCategoriesByDepartment($departmentId)
    {
        $user = auth()->user();
        $userDepartments = $user->departments ?? collect();
        
        // Check if user has view any permissions or access to this specific department
        $hasViewAnyPermission = $user->can('view any department') || $user->can('view any document');
        $hasAccessToDepartment = $userDepartments->pluck('id')->contains($departmentId);
        
        if (! $hasViewAnyPermission && ! $hasAccessToDepartment) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Use the same visibility rules as the main donut chart
        $visibleDocumentsQuery = $this->getVisibleDocumentsQuery();

        // documents reference subcategories; join through subcategories to aggregate by category
        $visibleCategoryCounts = (clone $visibleDocumentsQuery)
            ->where('department_id', $departmentId)
            ->join('subcategories', 'documents.subcategory_id', '=', 'subcategories.id')
            ->selectRaw('subcategories.category_id as category_id, COUNT(*) as documents_count')
            ->groupBy('subcategories.category_id')
            ->pluck('documents_count', 'category_id');

        $categories = Category::where('department_id', $departmentId)
            ->whereIn('id', $visibleCategoryCounts->keys())
            ->get()
            ->map(function ($category) use ($visibleCategoryCounts) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'count' => $visibleCategoryCounts[$category->id] ?? 0,
                ];
            });

        $department = Department::find($departmentId);

        return response()->json([
            'type' => 'categories',
            'data' => $categories,
            'title' => ui_t('pages.dashboard.donut.categories_title'),
            'subtitle' => ui_t('pages.dashboard.donut.categories_in', ['name' => $department?->name ?? '']),
        ]);
    }

    /**
     * Resolve all service IDs the current user is allowed to see on the dashboard
     * based on department, sub-department and direct service assignments.
     */
    private function getAccessibleServiceIds(): Collection
    {
        $user = auth()->user();
        if (! $user) {
            return collect();
        }

        // Super admins can see all services
        if ($user->hasRole('master') || $user->hasRole('super administrator') || $user->hasRole('super_admin')) {
            return Service::pluck('id');
        }

        $serviceIds = collect();
        
        // Check if this is a service-level user (has 'view service document' permission)
        $isServiceLevelUser = $user->can('view service document');

        // ALWAYS include directly assigned services
        // 1. Direct service_id column assignment
        if ($user->service_id) {
            $serviceIds->push($user->service_id);
        }
        
        // 2. Many-to-many service assignments via pivot
        if ($user->relationLoaded('services') || method_exists($user, 'services')) {
            $serviceIds = $serviceIds->merge($user->services->pluck('id'));
        }

        // ONLY include sub-department/department services for NON-service-level users
        // Service Managers should ONLY see their directly assigned services above
        if (! $isServiceLevelUser) {
            // Sub-department assignment: all services under the primary sub-department
            if ($user->sub_department_id) {
                $serviceIds = $serviceIds->merge(
                    Service::where('sub_department_id', $user->sub_department_id)->pluck('id')
                );
            }

            // Additional sub-departments via pivot: include all their services
            if ($user->relationLoaded('subDepartments') || method_exists($user, 'subDepartments')) {
                $extraSubDeptIds = $user->subDepartments->pluck('id');
                if ($extraSubDeptIds->isNotEmpty()) {
                    $serviceIds = $serviceIds->merge(
                        Service::whereIn('sub_department_id', $extraSubDeptIds)->pluck('id')
                    );
                }
            }

            // Departments assignment: all services under those departments
            $departmentIds = $user->departments->pluck('id');
            if ($departmentIds->isNotEmpty()) {
                $subDeptIds = SubDepartment::whereIn('department_id', $departmentIds)->pluck('id');
                if ($subDeptIds->isNotEmpty()) {
                    $serviceIds = $serviceIds->merge(
                        Service::whereIn('sub_department_id', $subDeptIds)->pluck('id')
                    );
                }
            }
        }

        return $serviceIds->unique()->filter();
    }

    public function getSubDepartmentsByDepartment($departmentId)
    {
        $user = auth()->user();
        $accessibleServiceIds = $this->getAccessibleServiceIds();

        // Ensure user can see this department at all
        $userDepartments = $user->departments ?? collect();
        $hasViewAnyPermission = $user->can('view any department') || $user->can('view any document');

        // Access via explicit department assignment
        $hasAccessToDepartment = $userDepartments->pluck('id')->contains($departmentId);

        // Or via any sub-department the user belongs to under this department (pivot or primary)
        $userSubDeptIds = $user->subDepartments->pluck('id')
            ->when($user->sub_department_id, function ($col) use ($user) {
                return $col->push($user->sub_department_id);
            });
        if (! $hasAccessToDepartment && $userSubDeptIds->isNotEmpty()) {
            $hasAccessToDepartment = SubDepartment::where('department_id', $departmentId)
                ->whereIn('id', $userSubDeptIds)
                ->exists();
        }

        if (! $hasViewAnyPermission && ! $hasAccessToDepartment) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $visibleDocumentsQuery = $this->getVisibleDocumentsQuery();
        $department = Department::find($departmentId);

        $isGlobalAdmin = $user && ($user->hasRole('master') || $user->hasRole('Super Administrator') || $user->hasRole('super administrator') || $user->hasRole('super_admin'));

        // ------------------------------------------------------------------
        // Global admins: ALWAYS drill down departments -> sub-departments
        // (even if there are no documents yet), then services, then categories.
        // ------------------------------------------------------------------
        if ($isGlobalAdmin) {
            $subDepartments = SubDepartment::where('department_id', $departmentId)
                ->orderBy('name')
                ->get()
                ->map(function ($subDept) use ($visibleDocumentsQuery) {
                    $serviceIds = $subDept->services->pluck('id');

                    $documentCount = $serviceIds->isEmpty()
                        ? 0
                        : (clone $visibleDocumentsQuery)->whereIn('service_id', $serviceIds)->count();

                    return [
                        'id' => $subDept->id,
                        'name' => $subDept->name,
                        'count' => $documentCount,
                    ];
                })
                ->values();

            return response()->json([
                'type' => 'sub_departments',
                'data' => $subDepartments,
                'title' => ui_t('pages.dashboard.donut.sub_departments_title'),
                'subtitle' => ui_t('pages.dashboard.donut.sub_departments_click'),
            ]);
        }

        // ------------------------------------------------------------------
        // Non-global users: keep existing behaviour (sub-departments -> services -> categories)
        // while respecting accessible services.
        // ------------------------------------------------------------------
        $subDepartments = SubDepartment::where('department_id', $departmentId)
            ->whereHas('services', function ($q) use ($accessibleServiceIds) {
                if ($accessibleServiceIds->isNotEmpty()) {
                    $q->whereIn('id', $accessibleServiceIds);
                }
            })
            ->get()
            ->map(function ($subDept) use ($accessibleServiceIds, $visibleDocumentsQuery) {
                // Count documents in services under this sub-department the user can see
                $serviceIds = $subDept->services()
                    ->when($accessibleServiceIds->isNotEmpty(), function ($q) use ($accessibleServiceIds) {
                        $q->whereIn('id', $accessibleServiceIds);
                    })
                    ->pluck('id');

                $documentCount = $serviceIds->isEmpty()
                    ? 0
                    : (clone $visibleDocumentsQuery)->whereIn('service_id', $serviceIds)->count();

                return [
                    'id' => $subDept->id,
                    'name' => $subDept->name,
                    'count' => $documentCount,
                ];
            });

        // If no sub-departments exist, fall back to showing services under this department
        if ($subDepartments->isEmpty()) {
            // Get all services under this department (via all its sub-departments)
            $services = Service::whereHas('subDepartment', function ($q) use ($departmentId) {
                    $q->where('department_id', $departmentId);
                })
                ->when($accessibleServiceIds->isNotEmpty(), function ($q) use ($accessibleServiceIds) {
                    $q->whereIn('id', $accessibleServiceIds);
                })
                ->get()
                ->map(function ($service) use ($visibleDocumentsQuery) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'count' => (clone $visibleDocumentsQuery)->where('service_id', $service->id)->count(),
                    ];
                })
                ->filter(fn ($s) => $s['count'] > 0)
                ->values();

            // If there are services, show them
            if ($services->isNotEmpty()) {
                return response()->json([
                    'type' => 'services',
                    'data' => $services,
                    'title' => ui_t('pages.dashboard.donut.services_title'),
                    'subtitle' => ui_t('pages.services_in', ['name' => $department?->name ?? '']),
                ]);
            }

            // Otherwise, fall back to categories
            $visibleCategoryCounts = (clone $visibleDocumentsQuery)
                ->where('department_id', $departmentId)
                ->whereNotNull('category_id')
                ->selectRaw('category_id, COUNT(*) as documents_count')
                ->groupBy('category_id')
                ->pluck('documents_count', 'category_id');

            $categories = Category::whereIn('id', $visibleCategoryCounts->keys())
                ->orderBy('name')
                ->get()
                ->map(function ($category) use ($visibleCategoryCounts) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'count' => $visibleCategoryCounts[$category->id] ?? 0,
                    ];
                })
                ->filter(fn ($cat) => $cat['count'] > 0)
                ->values();

            return response()->json([
                'type' => 'categories',
                'data' => $categories,
                'title' => ui_t('pages.dashboard.donut.categories_title'),
                'subtitle' => ui_t('pages.categories_in', ['name' => $department?->name ?? '']),
            ]);
        }

        return response()->json([
            'type' => 'sub_departments',
            'data' => $subDepartments,
            'title' => ui_t('pages.dashboard.donut.departments_title'),
            'subtitle' => ui_t('pages.sub_departments_of', ['name' => $department?->name ?? '']),
        ]);
    }

    public function getServicesBySubDepartment($subDepartmentId)
    {
        $user = auth()->user();
        $accessibleServiceIds = $this->getAccessibleServiceIds();

        $subDepartment = SubDepartment::findOrFail($subDepartmentId);

        // User must have access to this sub-department via departments/service hierarchy
        $hasViewAnyPermission = $user->can('view any department') || $user->can('view any document');

        // Access if user belongs to the parent department
        $belongsToDept = $user->departments->pluck('id')->contains($subDepartment->department_id);

        // Or if this sub-department is explicitly assigned (primary or pivot)
        $userSubDeptIds = $user->subDepartments->pluck('id')
            ->when($user->sub_department_id, function ($col) use ($user) {
                return $col->push($user->sub_department_id);
            });
        $belongsToThisSubDept = $userSubDeptIds->contains($subDepartmentId);

        if (! $hasViewAnyPermission && ! $belongsToDept && ! $belongsToThisSubDept) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $visibleDocumentsQuery = $this->getVisibleDocumentsQuery();

        $services = Service::where('sub_department_id', $subDepartmentId)
            ->when($accessibleServiceIds->isNotEmpty(), function ($q) use ($accessibleServiceIds) {
                $q->whereIn('id', $accessibleServiceIds);
            })
            ->get()
            ->map(function ($service) use ($visibleDocumentsQuery) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'count' => (clone $visibleDocumentsQuery)->where('service_id', $service->id)->count(),
                ];
            });

        return response()->json([
            'type' => 'services',
            'data' => $services,
            'title' => ui_t('pages.dashboard.donut.departments_title'),
            'subtitle' => ui_t('pages.services_of', ['name' => $subDepartment->name]),
        ]);
    }

    public function getCategoriesByService($serviceId)
    {
        $user = auth()->user();
        $accessibleServiceIds = $this->getAccessibleServiceIds();

        // Ensure service is visible to this user (unless super admin)
        if ($accessibleServiceIds->isNotEmpty() && ! $accessibleServiceIds->contains($serviceId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $service = Service::findOrFail($serviceId);
        $visibleDocumentsQuery = $this->getVisibleDocumentsQuery();

        // ------------------------------------------------------------------
        // Step 1: aggregate VISIBLE documents by category within this service
        // ------------------------------------------------------------------
        $visibleCategoryCounts = (clone $visibleDocumentsQuery)
            ->where('service_id', $serviceId)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, COUNT(*) as documents_count')
            ->groupBy('category_id')
            ->pluck('documents_count', 'category_id');

        // Use Category WITHOUT the service_hierarchy global scope here.
        $categoriesQuery = Category::withoutGlobalScopes()
            ->where('service_id', $serviceId)
            ->orderBy('name');

        $categories = $categoriesQuery->get();

        // ------------------------------------------------------------------
        // Step 2: if no service-bound categories exist (common when
        //         categories are department-scoped), fall back to
        //         department categories for this service's department.
        // ------------------------------------------------------------------
        if ($categories->isEmpty() && $service->subDepartment) {
            $departmentId = $service->subDepartment->department_id;
            if ($departmentId) {
                $visibleCategoryCounts = (clone $visibleDocumentsQuery)
                    ->where('service_id', $serviceId) // Fix: Count documents for this SERVICE, not the whole department
                    ->whereNotNull('category_id')
                    ->selectRaw('category_id, COUNT(*) as documents_count')
                    ->groupBy('category_id')
                    ->pluck('documents_count', 'category_id');

                $categories = Category::withoutGlobalScopes()
                    ->where('department_id', $departmentId)
                    ->orderBy('name')
                    ->get();
            }
        }
        $categories = $categories->map(function ($category) use ($visibleCategoryCounts) {
            return [
                'id'    => $category->id,
                'name'  => $category->name,
                'count' => $visibleCategoryCounts[$category->id] ?? 0,
            ];
        });

        // Check for Uncategorized documents (null category)
        $uncategorizedCount = (clone $visibleDocumentsQuery)
            ->where('service_id', $serviceId)
            ->whereNull('category_id')
            ->count();

        if ($uncategorizedCount > 0) {
            $uncategorizedLabel = ui_t('pages.uncategorized');
            if ($uncategorizedLabel === 'pages.uncategorized') {
                $uncategorizedLabel = 'Uncategorized';
            }
            
            $categories->push([
                'id'    => 'uncategorized',
                'name'  => $uncategorizedLabel,
                'count' => $uncategorizedCount,
            ]);
        }

        return response()->json([
            'type' => 'categories',
            'data' => $categories,
            'service_id' => $serviceId, // Pass service ID for context
            'title' => ui_t('pages.dashboard.donut.categories_title'),
            'subtitle' => ui_t('pages.categories_in', ['name' => $service->name]),
        ]);
    }

    private function getStatusSummary()
    {
        $statusCounts = $this->getVisibleDocumentsQuery()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // Count expired documents (expire_at has passed)
        $expiredCount = $this->getVisibleDocumentsQuery()
            ->whereNotNull('expire_at')
            ->where('expire_at', '<=', now())
            ->count();

        return [
            DocumentStatus::Approved->value => $statusCounts['approved'] ?? 0,
            DocumentStatus::Pending->value  => $statusCounts['pending'] ?? 0,
            DocumentStatus::Declined->value => $statusCounts['declined'] ?? 0,
            'expired' => $expiredCount,
        ];
    }

    private function getWeeklyData()
    {
        $now = Carbon::now();
        $statuses = ['pending', 'approved', 'declined', 'deleted'];

        $rawWeekly = $this->getVisibleDocumentsQuery()
            ->selectRaw('DAYNAME(created_at) as day, status, COUNT(*) as total')
            ->whereBetween('created_at', [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()])
            ->groupBy('day', 'status')
            ->get();

        // Get expired documents
        $expiredWeekly = $this->getVisibleDocumentsQuery()
            ->selectRaw('DAYNAME(created_at) as day, COUNT(*) as total')
            ->whereNotNull('expire_at')
            ->where('expire_at', '<=', now())
            ->whereBetween('created_at', [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()])
            ->groupBy('day')
            ->get();

        $daysOrder = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        $weekly = [];
        foreach ($daysOrder as $day) {
            $weekly[$day] = array_fill_keys($statuses, 0);
            $weekly[$day]['day'] = $day;
        }
        foreach ($rawWeekly as $row) {
            $weekly[$row->day][$row->status] = $row->total;
        }
        // Add expired counts
        foreach ($expiredWeekly as $row) {
            $weekly[$row->day]['expired'] = $row->total;
        }

        return array_values($weekly);
    }

    private function getMonthlyData()
    {
        $now = Carbon::now();
        $statuses = ['pending', 'approved', 'declined', 'deleted'];

        $rawMonthly = $this->getVisibleDocumentsQuery()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, status, COUNT(*) as total')
            ->whereBetween('created_at', [$now->copy()->subMonths(11)->startOfMonth(), $now->copy()->endOfMonth()])
            ->groupBy('month', 'status')
            ->get();

        // Get expired documents
        $expiredMonthly = $this->getVisibleDocumentsQuery()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as total')
            ->whereNotNull('expire_at')
            ->where('expire_at', '<=', now())
            ->whereBetween('created_at', [$now->copy()->subMonths(11)->startOfMonth(), $now->copy()->endOfMonth()])
            ->groupBy('month')
            ->get();

        $monthly = [];
        $startOfYear = $now->copy()->startOfYear();

        for ($i = 0; $i < 12; $i++) {
            $monthKey = $startOfYear->copy()->addMonths($i)->format('Y-m');
            $monthly[$monthKey] = array_fill_keys($statuses, 0);
            $monthly[$monthKey]['month'] = $monthKey;
        }

        foreach ($rawMonthly as $row) {
            $monthly[$row->month][$row->status] = $row->total;
        }
        
        // Add expired counts
        foreach ($expiredMonthly as $row) {
            if (isset($monthly[$row->month])) {
                $monthly[$row->month]['expired'] = $row->total;
            }
        }

        return array_values($monthly);
    }

    private function getYearlyData()
    {
        $now = Carbon::now();
        $statuses = ['pending', 'approved', 'declined', 'deleted'];

        $rawYearly = $this->getVisibleDocumentsQuery()
            ->selectRaw('YEAR(created_at) as year, status, COUNT(*) as total')
            ->whereBetween('created_at', [$now->copy()->subYears(4)->startOfYear(), $now->copy()->endOfYear()])
            ->groupBy('year', 'status')
            ->get();

        // Get expired documents
        $expiredYearly = $this->getVisibleDocumentsQuery()
            ->selectRaw('YEAR(created_at) as year, COUNT(*) as total')
            ->whereNotNull('expire_at')
            ->where('expire_at', '<=', now())
            ->whereBetween('created_at', [$now->copy()->subYears(4)->startOfYear(), $now->copy()->endOfYear()])
            ->groupBy('year')
            ->get();

        $yearly = [];
        for ($i = 0; $i < 5; $i++) {
            $yearKey = $now->copy()->subYears(4 - $i)->format('Y');
            $yearly[$yearKey] = array_fill_keys($statuses, 0);
            $yearly[$yearKey]['year'] = $yearKey;
        }

        foreach ($rawYearly as $row) {
            $yearly[$row->year][$row->status] = $row->total;
        }
        
        // Add expired counts
        foreach ($expiredYearly as $row) {
            if (isset($yearly[$row->year])) {
                $yearly[$row->year]['expired'] = $row->total;
            }
        }

        return array_values($yearly);
    }

    /**
     * Convert bytes to a human-readable string (e.g. 1.23 GB).
     *
     * Previous implementation skipped the "B" unit and started from "KB",
     * which caused every value to be shifted by one unit (GB shown as TB, etc.).
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = (float) $bytes;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return number_format($bytes, 2) . ' ' . $units[$i];
    }

}
