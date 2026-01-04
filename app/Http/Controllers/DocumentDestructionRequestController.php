<?php

namespace App\Http\Controllers;

use App\Enums\DocumentDestructionStatus;
use App\Enums\DocumentStatus;
use App\Exports\DestructionRequestsExport;
use App\Models\AuditLog;
use App\Models\DocumentDestructionRequest;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class DocumentDestructionRequestController extends Controller
{
    // List all expired documents (ready for destruction or postponement)
    public function index()
    {
        $user = auth()->user();
        if (! $user || ! $user->hasAnyRole(['master', 'Super Administrator', 'super administrator', 'Admin de pole', 'admin de pôle', 'Admin de departments', 'Admin de cellule', 'Service Manager', 'Department Administrator'])) {
            abort(403);
        }

        // Show documents that have expired in real-time (expire_at is in the past)
        // Must use withoutGlobalScopes() because Document model has a global scope
        // that hides expired documents from normal queries
        $query = \App\Models\Document::withoutGlobalScopes()
            ->with(['latestVersion', 'createdBy', 'department'])
            ->whereNotNull('expire_at')
            ->where('expire_at', '<=', now())
            ->whereNull('deleted_at');  // Exclude soft-deleted documents
        
        // Department Administrator: only see expired documents from their departments
        // Admin/Super Admin: see all expired documents from all departments
        $isDeptAdmin = $user->hasRole('Department Administrator') || $user->hasRole('Admin de pole') || $user->hasRole('Admin de departments');
        $isServiceManager = $user->hasRole('Admin de cellule') || $user->hasRole('Service Manager');
        $isAdmin = $user->hasRole(['master', 'Super Administrator', 'super administrator']);
        
        if ($isDeptAdmin && !$isAdmin) {
            $deptIds = $user->departments?->pluck('id') ?? collect();
            $query->whereIn('documents.department_id', $deptIds->all());
        } elseif ($isServiceManager && !$isAdmin) {
            // Service Manager: Strict service filtering
            // 1. Direct service assignment
            $serviceIds = collect();
            if ($user->service_id) {
                $serviceIds->push($user->service_id);
            }
            // 2. Pivot services
            if ($user->relationLoaded('services') || method_exists($user, 'services')) {
                $serviceIds = $serviceIds->merge($user->services->pluck('id'));
            }
            $serviceIds = $serviceIds->unique()->filter();

            if ($serviceIds->isNotEmpty()) {
                $query->whereIn('documents.service_id', $serviceIds->all());
            } else {
                $query->whereRaw('1 = 0');
            }
        }
        
        $expiredDocuments = $query->latest()->paginate(10);

        return view('documents-destructions.index', ['expiredDocuments' => $expiredDocuments]);
    }

    /**
     * Show log of permanently deleted documents (Deletion log).
     */
    public function deletionLogs()
    {
        // We might need to adjust the policy check if it fails for 'Admin de cellule', 
        // but typically 'viewAny' might be open or we fix the policy separately. 
        // For now, assuming the controller gate was the main blocker or policy allows it if we fix permissions.
        // If 403 persists, we check Policy.

        $user = auth()->user();
        
        // Debug logging
        if ($user) {
            \Illuminate\Support\Facades\Log::info("DeletionLog View: User {$user->id} roles: " . $user->getRoleNames()->implode(', '));
        }

        if (! $user || ! $user->hasAnyRole(['master', 'Super Administrator', 'super administrator', 'Admin de pole', 'admin de pôle', 'Admin de departments', 'Admin de cellule', 'Service Manager', 'Department Administrator'])) {
            \Illuminate\Support\Facades\Log::warning("DeletionLog View: 403 Forbidden for User {$user->id}");
            abort(403);
        }

        $query = AuditLog::with(['user', 'document' => function ($q) {
                $q->withTrashed()->with(['department', 'service.subDepartment']);
            }])
            ->where('action', 'permanently_deleted');

        $isDeptAdmin = $user->hasRole('Department Administrator') || $user->hasRole('Admin de pole') || $user->hasRole('Admin de departments');
        $isServiceManager = $user->hasRole('Admin de cellule') || $user->hasRole('Service Manager');
        $isAdmin = $user->hasRole(['master', 'Super Administrator', 'super administrator']);

        if ($isDeptAdmin && !$isAdmin) {
            $deptIds = $user->departments?->pluck('id') ?? collect();
            $query->whereHas('document', function($q) use ($deptIds) {
                $q->withTrashed()->whereIn('documents.department_id', $deptIds);
            });
        } elseif ($isServiceManager && !$isAdmin) {
             // Service Manager: Strict service filtering
            $serviceIds = collect();
            if ($user->service_id) {
                $serviceIds->push($user->service_id);
            }
            if ($user->relationLoaded('services') || method_exists($user, 'services')) {
                $serviceIds = $serviceIds->merge($user->services->pluck('id'));
            }
            $serviceIds = $serviceIds->unique()->filter();

            if ($serviceIds->isNotEmpty()) {
                 $query->whereHas('document', function($q) use ($serviceIds) {
                    $q->withTrashed()->whereIn('documents.service_id', $serviceIds);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $logs = $query->orderByDesc('occurred_at')->paginate(10);

        return view('users.deletion-logs', compact('logs'));
    }

    /**
     * Export deletion logs to Excel
     */
    public function exportDeletionLogs()
    {
        $user = auth()->user();
        if (! $user || ! $user->hasAnyRole(['master', 'Super Administrator', 'super administrator', 'Admin de pole', 'admin de pôle', 'Admin de departments', 'Admin de cellule', 'Service Manager', 'Department Administrator'])) {
            abort(403);
        }

        $query = AuditLog::with(['user', 'document' => function ($q) {
                $q->withTrashed()->with(['department', 'service.subDepartment']);
            }])
            ->where('action', 'permanently_deleted');

        $isDeptAdmin = $user->hasRole('Department Administrator') || $user->hasRole('Admin de pole') || $user->hasRole('Admin de departments');
        $isServiceManager = $user->hasRole('Admin de cellule') || $user->hasRole('Service Manager');
        $isAdmin = $user->hasRole(['master', 'Super Administrator', 'super administrator']);

        if ($isDeptAdmin && !$isAdmin) {
            $deptIds = $user->departments?->pluck('id') ?? collect();
            $query->whereHas('document', function($q) use ($deptIds) {
                $q->withTrashed()->whereIn('documents.department_id', $deptIds);
            });
        } elseif ($isServiceManager && !$isAdmin) {
             // Service Manager: Strict service filtering
            $serviceIds = collect();
            if ($user->service_id) {
                $serviceIds->push($user->service_id);
            }
            if ($user->relationLoaded('services') || method_exists($user, 'services')) {
                $serviceIds = $serviceIds->merge($user->services->pluck('id'));
            }
            $serviceIds = $serviceIds->unique()->filter();

            if ($serviceIds->isNotEmpty()) {
                 $query->whereHas('document', function($q) use ($serviceIds) {
                    $q->withTrashed()->whereIn('documents.service_id', $serviceIds);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $logs = $query->orderByDesc('occurred_at')->get();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\DeletionLogsExport($logs),
            'deletion-logs-' . now()->format('Ymd_His') . '.xlsx'
        );
    }


    // Store a new request
    public function store(Request $request)
    {
        Gate::authorize('create', DocumentDestructionRequest::class);

        $data = $request->validate([
            'document_id' => 'required|exists:documents,id',
            'implementation_id' => 'nullable|exists:document_movements,id'
        ]);

        $data['status'] = 'pending';
        $data['requested_by'] = Auth::id();
        $data['requested_at'] = now();
        $req = DocumentDestructionRequest::create($data);

        // Immediately mark document as destroyed (physical copy removed, keep location unchanged)
        $document = $req->document;
        $document->status = \App\Enums\DocumentStatus::Destroyed;
        $document->save();
        $document->logAction('destroyed');
        $action = 'destruction requested';

        $admins = User::role('admin')->get();

        foreach ($admins as $admin) {
            $admin->notify(new GeneralNotification(
                'info',
                "Document $action",
                "A new destruction request has been submitted for the document \"{$document->title}\" by " . auth()->user()->name . ".",
                $document->id,
                $document->latestVersion->id,
                $action
            ));
        }


        return redirect()->back()->with('success', 'Destruction Request created.');
    }


    // Update request
    public function update(Request $request, DocumentDestructionRequest $destructionRequest)
    {

        Gate::authorize('update', $destructionRequest);


        $data = $request->validate([
            'document_id' => 'required|exists:documents,id',
            'status' => 'required|string',
            'implementation_id' => 'nullable|exists:document_movements,id',
            'implemented_at' => 'nullable|date',
        ]);

        $destructionRequest->update($data);

        return redirect()->route('destruction-requests.index')->with('success', 'Request updated.');
    }

    // Delete request
    public function destroy(DocumentDestructionRequest $destructionRequest)
    {
        Gate::authorize('delete', $destructionRequest);

        $destructionRequest->delete();

        return redirect()->route('destruction-requests.index')->with('success', 'Request deleted.');
    }

    public function approve($id)
    {
        Gate::authorize('approve', DocumentDestructionRequest::class);

        $destruction = DocumentDestructionRequest::findOrFail($id);

        $destruction->document->status = DocumentStatus::Destroyed;
        $destruction->document->save();

        $destruction->status = DocumentDestructionStatus::Accepted;
        $destruction->save();

        $destruction->document->logAction('destroyed');

        return back()->with('success', 'Document Destruction approved.');
    }

    public function decline($id)
    {
        Gate::authorize('decline', DocumentDestructionRequest::class);

        $destruction = DocumentDestructionRequest::findOrFail($id);
        $destruction->status = DocumentDestructionStatus::Rejected;
        $destruction->save();

        return back()->with('success', 'Document Destruction approved.');
    }

    /**
     * Postpone document expiration by adding time
     */
    public function postpone($id, Request $request)
    {
        Gate::authorize('postpone', DocumentDestructionRequest::class);

        $destruction = DocumentDestructionRequest::findOrFail($id);
        $document = $destruction->document;

        if (! $document) {
            return back()->with('error', 'Document not found.');
        }

        if (! $document->expire_at) {
            return back()->with('error', 'This document does not have an expiry date set.');
        }

        $validated = $request->validate([
            'amount' => 'required|integer|min:1|max:1000',
            'unit' => 'required|in:minutes,hours,days,weeks,months,years'
        ]);

        $amount = (int) $validated['amount'];
        $unit = $validated['unit'];

        // Store original expiry
        $originalExpiry = $document->expire_at->copy();

        // Add time based on unit
        switch ($unit) {
            case 'minutes':
                $document->expire_at = $document->expire_at->addMinutes($amount);
                break;
            case 'hours':
                $document->expire_at = $document->expire_at->addHours($amount);
                break;
            case 'days':
                $document->expire_at = $document->expire_at->addDays($amount);
                break;
            case 'weeks':
                $document->expire_at = $document->expire_at->addWeeks($amount);
                break;
            case 'months':
                $document->expire_at = $document->expire_at->addMonths($amount);
                break;
            case 'years':
                $document->expire_at = $document->expire_at->addYears($amount);
                break;
        }

        // Change status back to approved when coming from destroyed
        if ($document->status === DocumentStatus::Destroyed->value) {
            $document->status = DocumentStatus::Approved->value;
        }
        
        // Clear expired flag to make document visible again
        $document->is_expired = false;

        $document->save();

        // Log the action (let Document::logAction resolve the version id itself)
        $document->logAction('expiration_postponed');

        // Mark destruction request as postponed
        $destruction->status = DocumentDestructionStatus::Postponed;
        $destruction->save();

        return back()->with('success', "Expiration postponed by {$amount} {$unit}. New expiry: {$document->expire_at->format('Y-m-d H:i:s')}.");
    }

    /**
     * Postpone document expiration directly (without destruction request)
     */
    public function postponeDocument($documentId, Request $request)
    {
        Gate::authorize('postpone', DocumentDestructionRequest::class);

        // Use withoutGlobalScopes to allow postponing expired documents from destructions page
        $document = \App\Models\Document::withoutGlobalScopes()->findOrFail($documentId);

        if (! $document->expire_at) {
            return back()->with('error', 'This document does not have an expiry date set.');
        }

        $validated = $request->validate([
            'amount' => 'required|integer|min:1|max:1000',
            'unit' => 'required|in:minutes,hours,days,weeks,months,years'
        ]);

        $amount = (int) $validated['amount'];
        $unit = $validated['unit'];

        // Add time based on unit
        switch ($unit) {
            case 'minutes':
                $document->expire_at = $document->expire_at->addMinutes($amount);
                break;
            case 'hours':
                $document->expire_at = $document->expire_at->addHours($amount);
                break;
            case 'days':
                $document->expire_at = $document->expire_at->addDays($amount);
                break;
            case 'weeks':
                $document->expire_at = $document->expire_at->addWeeks($amount);
                break;
            case 'months':
                $document->expire_at = $document->expire_at->addMonths($amount);
                break;
            case 'years':
                $document->expire_at = $document->expire_at->addYears($amount);
                break;
        }

        // Change status back to approved if it was destroyed
        if ($document->status === DocumentStatus::Destroyed->value) {
            $document->status = DocumentStatus::Approved->value;
        }
        
        // Clear expired flag to make document visible again in normal pages
        $document->is_expired = false;

        $document->save();

        // Log the action
        $document->logAction('expiration_postponed');

        return back()->with('success', "Expiration postponed by {$amount} {$unit}. New expiry: {$document->expire_at->format('Y-m-d H:i:s')}. Document is now active again.");
    }

    public function export()
    {
        Gate::authorize('viewAny', DocumentDestructionRequest::class);
        return Excel::download(new DestructionRequestsExport(), 'destruction_requests_' . now()->format('Ymd_His') . '.xlsx');
    }
}
