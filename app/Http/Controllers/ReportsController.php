<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Department;
use App\Models\User;
use App\Models\Category;
use App\Models\SubDepartment;
use App\Models\Service;
use App\Services\DocumentSearchService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Gate;
use App\Exports\DocumentsReportExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Document::class);

        // NOTE: Access is allowed for Admin de departments, but all
        // data is automatically scoped to their assigned departments
        // by the logic below (see $isSuperAdmin and department filters).

        // Get filter data with proper permission and role-based scoping
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('master') || $user->hasRole('super administrator') || $user->hasRole('super_admin');

        // Rooms: only show rooms that contain documents (via rows → shelves → boxes → documents)
        $rooms = \App\Models\Room::whereHas('rows.shelves.boxes.documents')
            ->orderBy('name')
            ->pluck('name')
            ->values();
        
        // Departments: super admin sees all, others see only their own directions
        $departments = $isSuperAdmin
            ? Department::orderBy('name')->get()
            : $user->departments()->orderBy('name')->get();
            
        // Users: super admin sees all, others see users in their directions
        $users = $isSuperAdmin
            ? User::orderBy('full_name')->get()
            : User::whereHas('departments', function($query) use ($user) {
                $query->whereIn('departments.id', $user->departments->pluck('id'));
            })->orderBy('full_name')->get();
            
        // Categories: super admin sees all, others see categories in their directions
        $categories = $isSuperAdmin
            ? Category::orderBy('name')->get()
            : Category::whereIn('department_id', $user->departments->pluck('id'))->orderBy('name')->get();

        // Sub-departments: super admin sees all, others see sub-departments under their departments
        $subDepartments = $isSuperAdmin
            ? SubDepartment::orderBy('name')->get()
            : SubDepartment::whereIn('department_id', $user->departments->pluck('id'))->orderBy('name')->get();
        
        // Get years from documents
        $years = Document::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->filter()
            ->values();

        // Prepare filters for DocumentSearchService
        $filters = [];

        // For department-scoped roles (Department Administrator, Admin de pole),
        // always restrict to their assigned departments unless they are super admin
        if (! $isSuperAdmin && $user->departments && $user->departments->isNotEmpty()) {
            $filters['department_ids'] = $user->departments->pluck('id')->all();
        }

        // Room filter
        if ($request->filled('room')) {
            $room = \App\Models\Room::where('name', $request->room)->first();
            if ($room) {
                $boxIds = \App\Models\Box::whereHas('shelf.row.room', function($q) use ($room) {
                    $q->where('id', $room->id);
                })->pluck('id');
                $filters['box_id'] = $boxIds->toArray();
            }
        }

        // Department filter
        if ($request->filled('department_id')) {
            $filters['department_id'] = $request->department_id;
        }

        // User filter (for super admins)
        if ($request->filled('user_id') && $isSuperAdmin) {
            $filters['created_by'] = $request->user_id;
        }

        // Year filter
        if ($request->filled('year')) {
            $filters['date_from'] = $request->year . '-01-01';
            $filters['date_to'] = $request->year . '-12-31';
        }

        // Sub-department filter -> map to services under that sub-department
        if ($request->filled('sub_department_id')) {
            $serviceIds = Service::where('sub_department_id', $request->sub_department_id)->pluck('id')->toArray();
            if (!empty($serviceIds)) {
                $filters['service_ids'] = $serviceIds;
            }
        }

        // Use Elasticsearch for search, fallback to database for no search
        if ($request->filled('search')) {
            $documents = DocumentSearchService::searchDocuments($request->search, $filters, 20, $request->get('page', 1));
        } else {
            // Fallback to database query when no search term
            $query = Document::with(['subcategory', 'department', 'box.shelf.row.room', 'createdBy', 'latestVersion']);

            // Always scope to viewer's departments for department-level roles (non super admin)
            if (! $isSuperAdmin && $user->departments && $user->departments->isNotEmpty()) {
                $query->whereIn('documents.department_id', $user->departments->pluck('id'));
            }

            // Room filter
            if ($request->filled('room')) {
                $room = \App\Models\Room::where('name', $request->room)->first();
                if ($room) {
                    $boxIds = \App\Models\Box::whereHas('shelf.row.room', function($q) use ($room) {
                        $q->where('id', $room->id);
                    })->pluck('id');
                    $query->whereIn('box_id', $boxIds);
                }
            }

            // Department filter
            if ($request->filled('department_id')) {
                $query->where('documents.department_id', $request->department_id);
            }

            // User filter (for super admins)
            if ($request->filled('user_id') && $isSuperAdmin) {
                $query->where('created_by', $request->user_id);
            }

            // Year filter
            if ($request->filled('year')) {
                $query->whereYear('documents.created_at', $request->year);
            }

            // Sub-department filter (via services)
            if ($request->filled('sub_department_id')) {
                $serviceIds = Service::where('sub_department_id', $request->sub_department_id)->pluck('id');
                if ($serviceIds->isNotEmpty()) {
                    $query->whereIn('service_id', $serviceIds);
                }
            }

            // Get results
            $documents = $query->latest()->paginate(20);
        }

        // Get statistics
        $stats = $this->getStatistics($request);

        return view('reports.index', compact(
            'documents', 
            'rooms', 
            'departments', 
            'users', 
            'categories', 
            'subDepartments',
            'years',
            'stats'
        ));
    }

    public function export(Request $request)
    {
        Gate::authorize('viewAny', Document::class);

        // Admin de departments may export reports, but underlying
        // query in DocumentsReportExport should already be scoped
        // to their departments via existing permissions.

        return Excel::download(new DocumentsReportExport($request), 'documents_report_' . now()->format('Y-m-d_H-i-s') . '.xlsx');
    }

    public function getStatistics(Request $request): array
    {
        // Resolve current user and role-scope first
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->hasRole('master') || $user->hasRole('super administrator') || $user->hasRole('super_admin'));

        // Prepare filters for DocumentSearchService
        $filters = [];

        // For department-scoped roles (Department Administrator, Admin de pole),
        // always restrict to their assigned departments unless they are super admin
        if (! $isSuperAdmin && $user && $user->departments && $user->departments->isNotEmpty()) {
            $filters['department_ids'] = $user->departments->pluck('id')->all();
        }

        // Room filter
        if ($request->filled('room')) {
            $room = \App\Models\Room::where('name', $request->room)->first();
            if ($room) {
                $boxIds = \App\Models\Box::whereHas('shelf.row.room', function($q) use ($room) {
                    $q->where('id', $room->id);
                })->pluck('id');
                $filters['box_id'] = $boxIds->toArray();
            }
        }

        // Department filter
        if ($request->filled('department_id')) {
            $filters['department_id'] = $request->department_id;
        }

        // User filter (for super admins)
        if ($request->filled('user_id') && $isSuperAdmin) {
            $filters['created_by'] = $request->user_id;
        }

        // Year filter
        if ($request->filled('year')) {
            $filters['date_from'] = $request->year . '-01-01';
            $filters['date_to'] = $request->year . '-12-31';
        }

        // Sub-department filter -> map to services under that sub-department
        if ($request->filled('sub_department_id')) {
            $serviceIds = Service::where('sub_department_id', $request->sub_department_id)->pluck('id')->toArray();
            if (!empty($serviceIds)) {
                $filters['service_ids'] = $serviceIds;
            }
        }

        // Use Elasticsearch for statistics if search is provided, otherwise use database
        if ($request->filled('search')) {
            return DocumentSearchService::getStatistics($filters);
        }

        // Use Document::query() to apply the same filtering as the dashboard
        // This includes expiry filtering, destruction status filtering, and user permission scoping
        $baseQuery = Document::query();

        // Apply same filters for statistics
        // Note: Department scoping is already handled by the Document model's global scope
        // Only apply explicit filters if provided

        if ($request->filled('room')) {
            $room = \App\Models\Room::where('name', $request->room)->first();
            if ($room) {
                $boxIds = \App\Models\Box::whereHas('shelf.row.room', function($q) use ($room) {
                    $q->where('id', $room->id);
                })->pluck('id');
                $baseQuery->whereIn('box_id', $boxIds);
            }
        }

        if ($request->filled('department_id')) {
            $baseQuery->where('documents.department_id', $request->department_id);
        }

        if ($request->filled('user_id') && auth()->user()->hasRole(['master', 'super_admin', 'super administrator'])) {
            $baseQuery->where('created_by', $request->user_id);
        }

        if ($request->filled('year')) {
            $baseQuery->whereYear('documents.created_at', $request->year);
        }

        if ($request->filled('sub_department_id')) {
            $serviceIds = Service::where('sub_department_id', $request->sub_department_id)->pluck('id');
            if ($serviceIds->isNotEmpty()) {
                $baseQuery->whereIn('documents.service_id', $serviceIds);
            }
        }

        // Get total count
        $totalDocuments = $baseQuery->count();

        // Get status statistics
        $statusQuery = clone $baseQuery;
        $byStatus = $statusQuery->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Get department statistics
        $deptQuery = clone $baseQuery;
        $byDepartment = $deptQuery->join('departments', 'documents.department_id', '=', 'departments.id')
            ->selectRaw('departments.name, COUNT(*) as count')
            ->groupBy('departments.id', 'departments.name')
            ->pluck('count', 'name')
            ->toArray();

        // Get category statistics
        $catQuery = clone $baseQuery;
        $byCategory = $catQuery->join('subcategories', 'documents.subcategory_id', '=', 'subcategories.id')
            ->join('categories', 'subcategories.category_id', '=', 'categories.id')
            ->selectRaw('categories.name, COUNT(*) as count')
            ->groupBy('categories.id', 'categories.name')
            ->pluck('count', 'name')
            ->toArray();

        return [
            'total_documents' => $totalDocuments,
            'by_status' => $byStatus,
            'by_department' => $byDepartment,
            'by_category' => $byCategory,
        ];
    }
}
