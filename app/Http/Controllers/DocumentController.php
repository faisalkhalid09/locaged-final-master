<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Exports\DocumentsReportExport;
use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Subcategory;
use App\Models\SubDepartment;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Service;
use App\Services\PdfConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DocumentController extends Controller
{
    // Show all documents
    public function index()
    {
        Gate::authorize('viewAny', Document::class);
        return view('documents.index');
    }

    /**
     * Show a single document – redirect to its versions list / preview.
     * This satisfies the resource "show" route used by Laravel.
     */
    public function show(Document $document)
    {
        Gate::authorize('view', $document);

        // Redirect to the existing "by document" versions page
        return redirect()->route('document-versions.by-document', ['id' => $document->id]);
    }

    /**
     * Central destruction dashboard (redirects to destruction requests page).
     */
    public function destructions()
    {
        return redirect()->route('documents-destructions.index');
    }

    public function export(Request $request)
    {
        Gate::authorize('viewAny', Document::class);

        return Excel::download(new DocumentsReportExport($request), 'documents-report-' . now()->format('Ymd_His') . '.xlsx');
    }

    public function byCategory($categoryId = null)
    {
        Gate::authorize('viewAny', Document::class);

        $category = $categoryId ? Category::findOrFail($categoryId) : null;
        return view('documents.by-category',compact('category'));
    }

    public function bySubcategory($subcategoryId)
    {
        Gate::authorize('viewAny', Document::class);

        $subcategory = Subcategory::with('category')->findOrFail($subcategoryId);
        Gate::authorize('view', $subcategory->category);
        
        return view('documents.by-subcategory', compact('subcategory'));
    }

    public function showStatus(Request $request)
    {
        if (! Gate::any(['approve', 'decline'], Document::class)) {
            abort(403);
        }

        // Note: Expired documents are now always shown in pending approvals
        // The is_expired flag is maintained for visual indicators only
        
        $query = Document::with(['subcategory', 'department', 'box.shelf.row.room', 'createdBy'])
            ->where('status','pending');
        
        $documents = $query->latest()->paginate(10);

        return view('documents.status', compact('documents'));
    }

    // Show create form
    public function create()
    {
        Gate::authorize('create', Document::class);


        return view('documents.create');
    }

    // Store a new document
    public function store(Request $request)
    {
        Gate::authorize('create', Document::class);

        $maxFileSizeKb    = (int) config('uploads.max_file_size_kb', 50000);
        $allowedExtensions = config('uploads.allowed_extensions', []);
        $mimesPart         = !empty($allowedExtensions)
            ? '|mimes:' . implode(',', $allowedExtensions)
            : '';

        $validated = $request->validate([
            'file' => 'required|file|max:' . $maxFileSizeKb . $mimesPart,
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'box_id' => 'required|exists:boxes,id',
            'created_at' => 'required|date',
            'expire_at' => 'nullable|date',
            'metadata' => 'required|array',
            'metadata.author' => 'required|string',
            'metadata.color' => 'required|string',
            'metadata.email' => 'nullable|email',
            // Removed position/phone/location/orientation from metadata; using department instead
        ]);

        $uid = uuid_create();
        $validated['uid'] = $uid;
        $validated['created_by'] = auth()->id();
        // Enforce non-editable metadata from authenticated user
        $validated['metadata']['author'] = auth()->user()?->full_name ?? auth()->user()?->name ?? ($validated['metadata']['author'] ?? '');
        // Note: department_id comes from form input (user selects from their assigned departments)

        // Always force email to the authenticated user's email, regardless of input
        $validated['metadata']['email'] = auth()->user()?->email ?? ($validated['metadata']['email'] ?? null);

        // Check for duplicates (case-insensitive title, same department, same creation date)
        $confirmDuplicate = $request->boolean('confirm_duplicate', false);
        
        if (!$confirmDuplicate) {
            $deptIds = auth()->user()?->departments->pluck('id')->toArray();
            if (!empty($deptIds)) {
                // Extract just the date portion for comparison (ignore time)
                $searchDate = \Carbon\Carbon::parse($validated['created_at'])->format('Y-m-d');
                
                $duplicates = Document::whereRaw('LOWER(title) = ?', [strtolower($validated['title'])])
                    ->whereIn('department_id', $deptIds)
                    ->whereDate('created_at', $searchDate)
                ->get(['id', 'title'])
                ->map(fn($d) => [
                    'id' => $d->id,
                    'title' => $d->title,
                    'url' => route('document-versions.by-document', ['id' => $d->id])
                ])
                ->toArray();

                if (!empty($duplicates)) {
                    return back()
                        ->withInput()
                        ->with('duplicates', $duplicates)
                        ->with('current_title', $validated['title']);
                }
            }
        }

        try {
            \DB::beginTransaction();

            // Create document
            $document = Document::create($validated);
            $extension = $request->file('file')->getClientOriginalExtension();
            $filename = $request->title . '_' . now()->format('His') . '.' . $extension;
            // Upload file to private storage
            $filePath = Storage::disk('local')->putFileAs('', $request->file('file'), $filename);

            // Determine version number (e.g., 1 for new document)
            $versionNumber = 1;

            // Create document version
            $docVersion = DocumentVersion::create([
                'document_id' => $document->id,
                'uploaded_by' => auth()->id(),
                'version_number' => $versionNumber,
                'file_path' => $filePath,
                'file_type' => getFileCategory($extension)
            ]);

            // Automatically convert Word/Excel uploads to PDF so previews are
            // immediately available. This is a best-effort; failures are just
            // logged by PdfConversionService.
            if (in_array($docVersion->file_type, ['doc', 'excel'], true)) {
                app(PdfConversionService::class)->convertToPdf($docVersion->file_path);
            }

            // OCR is now only triggered when a document is approved.
            // See Document::queueOcrIfNeeded() and the approve/bulkApprove flows.

            \DB::commit();

            return redirect()->route('documents.success')->with('success', 'Document created successfully.');
        } catch (\Exception $e) {
            \DB::rollBack();

            Log::error('Document creation failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['file' => 'Failed to upload document. Please try again.'])->withInput();
        }
    }



    // Update document
    public function update(Request $request, Document $document)
    {

        Gate::authorize('update', $document);

        $isExpired = $document->expire_at && $document->expire_at->isPast();
        $user = auth()->user();
        $canChangeExpiry = $user && ($user->hasRole('master') || $user->hasRole('Super Administrator'));

        if ($isExpired) {
            // Only allow updating expire_at and box_id
            $rules = [
                'box_id' => 'required|exists:boxes,id',
            ];
            if ($canChangeExpiry) {
                $rules['expire_at'] = 'required|date|after:today';
            }

            $data = $request->validate($rules);

            if ($canChangeExpiry && isset($data['expire_at'])) {
                $document->expire_at = $data['expire_at'];
            }
            $document->box_id = $data['box_id'];
            $document->save();
            
            // Note: Once expire_at is extended to the future, isExpired check will be false
            // on next update, allowing full editing again. Status remains unchanged.
        } else {
            $rules = [
                'department_id' => 'required|exists:departments,id',
                'sub_department_id' => 'nullable|exists:sub_departments,id',
                'service_id' => 'required|exists:services,id',
                'category_id' => 'required|exists:categories,id',
                'subcategory_id' => 'nullable|exists:subcategories,id',
                'title' => 'required|string|max:255',
                'color' => 'nullable|string',
                'created_at' => 'required|date',
                'tags' => 'required|array|min:1',
                'tags.*' => 'required|string|exists:tags,id',
                'box_id' => 'required|exists:boxes,id',
                'status' => 'nullable|string',
            ];

            if ($canChangeExpiry) {
                $rules['expire_at'] = 'required|date|after:created_at';
            }

            $data = $request->validate($rules);

            // Extract non-fillable fields
            $tags = $data['tags'];
            $color = $data['color'] ?? null;

            // Protect expire_at from non-privileged users
            if (! $canChangeExpiry) {
                unset($data['expire_at']);
            }

            unset($data['tags'], $data['color']);

            // Update metadata (color goes into JSON column)
            $metadata = $document->metadata ?? [];
            if ($color !== null) {
                $metadata['color'] = $color;
            }
            $data['metadata'] = $metadata;

            // Update document (includes metadata)
            $document->update($data);

            // Sync tags relationship
            $document->tags()->sync($tags);
        }



        return back()->with('success', 'Document updated.');
    }

    // Archive document
    public function destroy(Document $document)
    {
        Gate::authorize('delete', $document);

        // Only change status to archived, don't actually delete
        $document->status = DocumentStatus::Archived;
        $document->save();
        
        $document->logAction('archived');

        // Stay on the same page instead of redirecting
        return back()->with('success', 'Document archived successfully.');
    }

    public function approve($id)
    {

        Gate::authorize('approve', Document::class);

        $doc = Document::findOrFail($id);
        $doc->status = DocumentStatus::Approved->value;
        $doc->save();
        $doc->logAction('approved');

        // Queue OCR only once the document is approved.
        $doc->queueOcrIfNeeded();

        return back()->with('success', 'Document approved.');
    }

    public function decline($id)
    {
        Gate::authorize('decline', Document::class);

        $doc = Document::findOrFail($id);

        $doc->status = DocumentStatus::Declined->value;
        $doc->save();
        $doc->logAction('declined');

        return back()->with('success', 'Document rejected.');
    }

    public function lock($id)
    {

        $doc = Document::with('latestVersion')->findOrFail($id);
        Gate::authorize('update', $doc);

        $doc->latestVersion->locked_by = \auth()->id();
        $doc->latestVersion->locked_at = now();
        $doc->latestVersion->unlocked_at = null;
        $doc->latestVersion->save();
        $doc->status = DocumentStatus::Locked;
        $doc->save();
        $doc->logAction('locked');

        return back()->with('success', 'Document locked.');
    }

    public function unlock($id)
    {

        $doc = Document::with('latestVersion')->findOrFail($id);
        Gate::authorize('update', $doc);


        $doc->latestVersion->locked_by = \auth()->id();
        $doc->latestVersion->locked_at = null;
        $doc->latestVersion->unlocked_at = now();
        $doc->latestVersion->save();
        $doc->status = DocumentStatus::Unlocked;
        $doc->save();
        $doc->logAction('unlocked');

        return back()->with('success', 'Document unlocked.');
    }


    public function download($id)
    {
        $doc = Document::with('latestVersion')->findOrFail($id);
        Gate::authorize('view', $doc);

        if (!$doc->latestVersion) {
            return back()->with('error', 'No document version found.');
        }

        return DocumentVersionController::downloadFile($doc->latestVersion->id);

    }

    public function permanentDelete($id)
    {
        // Use withoutGlobalScopes to allow deleting expired documents from destructions page
        $document = Document::withoutGlobalScopes()->findOrFail($id);
        
        // Check if user can permanently delete this document (role-based via policy)
        Gate::authorize('permanentDelete', $document);
        
        // Resolve latest version id (may be null if something is inconsistent)
        $latestVersion = $document->latestVersion;

        // Log the action before deletion (pass version id explicitly if available)
        $document->logAction('permanently_deleted', $latestVersion?->id);
        
        // Remove from search index and delete all document versions and their files
        foreach ($document->documentVersions as $version) {
            // Remove from search index first, but ignore Elasticsearch connectivity issues
            try {
                $version->unsearchable();
            } catch (\Throwable $e) {
                Log::warning('Failed to remove version from search index during permanent delete', [
                    'version_id' => $version->id,
                    'error' => $e->getMessage(),
                ]);
            }
            
            if ($version->file_path && Storage::exists($version->file_path)) {
                Storage::delete($version->file_path);
            }
            
            // Prevent Scout from trying to sync (which crashes if ES is down)
            // We already tried unsearchable() manually above with error handling
            \App\Models\DocumentVersion::withoutSyncingToSearch(function () use ($version) {
                // Delete associated OCR job if it exists
                if ($version->ocrJob) {
                    $version->ocrJob->delete();
                }
                $version->delete();
            });
        }
        
        // Delete the document itself
        $document->delete();
        
        return redirect()->back()->with('success', ui_t('pages.documents.delete_success'));
    }

    public function rename($id,Request $request)
    {

        $doc = Document::findOrFail($id);

        Gate::authorize('update', $doc);

        $validated = $request->validate([
            'title' => 'required|string|max:255'
        ]);

        $doc->title = $validated['title'];
        $doc->save();
        $doc->logAction('renamed');

        return back()->with('success', 'Document renamed.');

    }

    public function getMetadata($id)
    {
        $document = Document::with([
            'department', 
            'service.subDepartment', 
            'category', 
            'tags', 
            'createdBy', 
            'latestVersion',
            'box.shelf.row.room'
        ])->findOrFail($id);

        Gate::authorize('view', $document);

        // Format physical location string
        $physicalLocation = null;
        if ($document->box) {
            $box = $document->box;
            $shelf = $box->shelf;
            $row = $shelf->row;
            $room = $row->room;
            $physicalLocation = "{$room->name} → {$row->name} → {$shelf->name} → {$box->name}";
        }

        // Determine sub-department (either from service or direct relationship if exists)
        $subDepartment = $document->service && $document->service->subDepartment 
            ? $document->service->subDepartment->name 
            : ($document->sub_department_id ? \App\Models\SubDepartment::find($document->sub_department_id)?->name : null);

        // Build hierarchical options available to the current user (same logic as preview sidebar)
        $user = auth()->user();
        $userDepartments = collect();
        $userSubDepartments = collect();
        $userServices = collect();

        if ($user) {
            $user->refresh();
            $user->loadMissing(['subDepartments', 'services']);

            // Departments from pivot, bypassing Department global scope
            $userDeptIdsRaw = DB::table('department_user')
                ->where('user_id', $user->id)
                ->pluck('department_id');

            $userDepartmentsRaw = Department::withoutGlobalScopes()
                ->whereIn('id', $userDeptIdsRaw)
                ->get();

            if ($user->hasRole('master') || $user->hasRole('Super Administrator')) {
                // Privileged users see the full hierarchy
                $userDepartments    = Department::withoutGlobalScopes()->orderBy('name')->get();
                $userSubDepartments = SubDepartment::with('department')->orderBy('name')->get();
                $userServices       = Service::with('subDepartment.department')->orderBy('name')->get();
            } else {
                // Non-admins are restricted to explicit assignments via pivots
                $userDepartments = $userDepartmentsRaw;

                // Sub-departments
                if ($user->hasRole('Department Administrator') || $user->hasRole('Admin de pole')) {
                    $deptIds = $userDepartments->pluck('id');
                    $userSubDepartments = SubDepartment::whereIn('department_id', $deptIds)->get();
                } else {
                    $userSubDepartments = $user->subDepartments;
                }

                // Services
                if ($user->hasRole('Division Chief') || $user->hasRole('Admin de departments') || $user->hasRole('Admin de cellule') || $user->hasRole('Service Manager')) {
                    $subIds = $userSubDepartments->pluck('id');
                    $userServices = Service::whereIn('sub_department_id', $subIds)->get();
                } elseif ($user->hasRole('Department Administrator') || $user->hasRole('Admin de pole')) {
                    $subIds = $userSubDepartments->pluck('id');
                    $userServices = Service::whereIn('sub_department_id', $subIds)->get();
                } else {
                    $userServices = $user->services;
                }
            }
        }

        // Filter categories to only show those belonging to services the user has access to
        $accessibleServiceIds = $userServices->pluck('id');
        $categories = $accessibleServiceIds->isNotEmpty()
            ? Category::whereIn('service_id', $accessibleServiceIds)->orderBy('name')->get()
            : Category::orderBy('name')->get();

        // Filter physical location options by document's service
        // Only show boxes that belong to the document's service, and their parent locations
        $serviceId = $document->service_id;
        
        // Get all boxes for this service
        $serviceBoxes = $serviceId 
            ? \App\Models\Box::with('shelf.row.room')
                ->where('service_id', $serviceId)
                ->orderBy('name')
                ->get()
            : \App\Models\Box::with('shelf.row.room')->orderBy('name')->get();
        
        // Extract unique room, row, shelf IDs from these boxes
        $validShelfIds = $serviceBoxes->pluck('shelf_id')->unique()->filter();
        $validShelves = \App\Models\Shelf::with('row')
            ->whereIn('id', $validShelfIds)
            ->orderBy('name')
            ->get();
        
        $validRowIds = $validShelves->pluck('row_id')->unique()->filter();
        $validRows = \App\Models\Row::with('room')
            ->whereIn('id', $validRowIds)
            ->orderBy('name')
            ->get();
        
        $validRoomIds = $validRows->pluck('room_id')->unique()->filter();
        $validRooms = \App\Models\Room::whereIn('id', $validRoomIds)
            ->orderBy('name')
            ->get();

        // Map to arrays for JSON response
        $allRooms = $validRooms->map(function($room) {
            return [
                'id' => $room->id,
                'name' => $room->name,
            ];
        })->values();

        $allRows = $validRows->map(function($row) {
            return [
                'id' => $row->id,
                'name' => $row->name,
                'room_id' => $row->room_id,
            ];
        })->values();

        $allShelves = $validShelves->map(function($shelf) {
            return [
                'id' => $shelf->id,
                'name' => $shelf->name,
                'row_id' => $shelf->row_id,
            ];
        })->values();

        $allBoxes = $serviceBoxes->map(function($box) {
            return [
                'id' => $box->id,
                'name' => $box->name,
                'shelf_id' => $box->shelf_id,
            ];
        })->values();

        // Determine current location hierarchy from document's box
        $room_id = null;
        $row_id = null;
        $shelf_id = null;
        if ($document->box) {
            $shelf_id = $document->box->shelf_id;
            $row_id = $document->box->shelf->row_id;
            $room_id = $document->box->shelf->row->room_id;
        }

        $metadata = [
            'id' => $document->id,
            'title' => $document->title,
            'status' => $document->status,
            'created_at' => $document->created_at->format('d/m/Y H:i'),
            'expire_at' => $document->expire_at ? $document->expire_at->format('d/m/Y') : null,
            'expire_at_raw' => $document->expire_at ? $document->expire_at->format('Y-m-d') : null,
            'created_by' => $document->createdBy ? $document->createdBy->full_name : 'Unknown',
            'file_type' => $document->latestVersion ? $document->latestVersion->file_type : null,
            'department' => $document->department ? $document->department->name : null,
            'sub_department' => $subDepartment,
            'service' => $document->service ? $document->service->name : null,
            'category' => $document->category ? $document->category->name : null,
            'department_id' => $document->department_id,
            'sub_department_id' => $document->sub_department_id,
            'service_id' => $document->service_id,
            'category_id' => $document->category_id,
            'box_id' => $document->box_id,
            'room_id' => $room_id,
            'row_id' => $row_id,
            'shelf_id' => $shelf_id,
            'departments' => $userDepartments->map(fn($d) => [
                'id' => $d->id,
                'name' => $d->name,
            ])->values(),
            'sub_departments' => $userSubDepartments->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'department_id' => $s->department_id,
            ])->values(),
            'services' => $userServices->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'sub_department_id' => $s->sub_department_id,
            ])->values(),
            'categories' => $categories->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'service_id' => $c->service_id,
            ])->values(),
            'rooms' => $allRooms,
            'rows' => $allRows,
            'shelves' => $allShelves,
            'boxes' => $allBoxes,
            'physical_location' => $physicalLocation,
            'tags' => $document->tags->pluck('name')->toArray(),
            'color' => $document->metadata['color'] ?? null,
        ];

        return response()->json([
            'success' => true,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Lightweight metadata update endpoint used by the metadata modal.
     * Allows updating category, physical location (box), and tags.
     */
    public function updateMetadata(Request $request, $id)
    {
        $document = Document::findOrFail($id);
        Gate::authorize('update', $document);

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'expire_at' => 'nullable|date',
            'department_id' => 'nullable|exists:departments,id',
            'sub_department_id' => 'nullable|exists:sub_departments,id',
            'service_id' => 'nullable|exists:services,id',
            'category_id' => 'nullable|exists:categories,id',
            'box_id' => 'nullable|exists:boxes,id',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string|max:50',
        ]);

        $changed = [];

        if (array_key_exists('title', $data) && $data['title'] !== $document->title) {
            $changed['title'] = [$document->title, $data['title']];
            $document->title = $data['title'];
        }

        if (array_key_exists('expire_at', $data)) {
            $newExpire = $data['expire_at'] ? now()->create($data['expire_at']) : null;
            $oldExpire = $document->expire_at;
            if (($oldExpire && !$newExpire) || (!$oldExpire && $newExpire) || ($oldExpire && $newExpire && $oldExpire->ne($newExpire))) {
                $changed['expire_at'] = [
                    $oldExpire?->format('Y-m-d'),
                    $newExpire?->format('Y-m-d'),
                ];
                $document->expire_at = $newExpire;
            }
        }

        foreach (['department_id', 'sub_department_id', 'service_id', 'category_id'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] != $document->{$field}) {
                $changed[$field] = [$document->{$field}, $data[$field]];
                $document->{$field} = $data[$field];
                
                // If category changed, recalculate expiration date automatically
                if ($field === 'category_id' && $data[$field]) {
                    $category = \App\Models\Category::find($data[$field]);
                    if ($category && $category->expiry_value && $category->expiry_unit) {
                        $createdAt = $document->created_at ?: now();
                        
                        try {
                            switch ($category->expiry_unit) {
                                case 'days':
                                    $newExpire = $createdAt->copy()->addDays($category->expiry_value);
                                    break;
                                case 'months':
                                    $newExpire = $createdAt->copy()->addMonthsNoOverflow($category->expiry_value);
                                    break;
                                case 'years':
                                    $newExpire = $createdAt->copy()->addYearsNoOverflow($category->expiry_value);
                                    break;
                                default:
                                    $newExpire = null;
                            }
                            
                            if (isset($newExpire)) {
                                $oldExpire = $document->expire_at;
                                if (($oldExpire && !$newExpire) || (!$oldExpire && $newExpire) || ($oldExpire && $newExpire && $oldExpire->ne($newExpire))) {
                                    $changed['expire_at'] = [
                                        $oldExpire?->format('Y-m-d'),
                                        $newExpire?->format('Y-m-d'),
                                    ];
                                    $document->expire_at = $newExpire;
                                }
                            }
                        } catch (\Throwable $e) {
                            // Ignore calculation errors, keep existing expiry
                        }
                    }
                }
            }
        }

        // Handle box_id (physical location)
        if (array_key_exists('box_id', $data) && $data['box_id'] != $document->box_id) {
            $changed['box_id'] = [$document->box_id, $data['box_id']];
            $document->box_id = $data['box_id'];
        }

        // Handle tags
        if (array_key_exists('tags', $data)) {
            $newTags = collect($data['tags'])->filter()->map(fn($tag) => trim($tag))->unique()->values();
            $oldTagNames = $document->tags->pluck('name')->sort()->values();
            $newTagNames = $newTags->sort()->values();
            
            if ($oldTagNames->toJson() !== $newTagNames->toJson()) {
                $changed['tags'] = [$oldTagNames->toArray(), $newTagNames->toArray()];
                
                // Create or find tags and sync
                $tagIds = [];
                foreach ($newTags as $tagName) {
                    if (!empty($tagName)) {
                        $tag = \App\Models\Tag::firstOrCreate(['name' => $tagName]);
                        $tagIds[] = $tag->id;
                    }
                }
                $document->tags()->sync($tagIds);
            }
        }

        if (!empty($changed)) {
            $document->save();
            $document->logAction('metadata_updated');
            
            // Flash success message for toast notification
            session()->flash('toast_success', __('pages.upload.metadata_updated'));
        }

        // Return refreshed metadata payload
        return $this->getMetadata($document->id);
    }

}
