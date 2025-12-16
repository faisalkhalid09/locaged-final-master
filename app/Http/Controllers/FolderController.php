<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Folder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class FolderController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Folder::class);

        $folders = Folder::with(['parent', 'department', 'service', 'creator'])
            ->orderBy('name')
            ->paginate(15);

        return view('folders.index', compact('folders'));
    }

    public function show(Folder $folder)
    {
        Gate::authorize('view', $folder);

        // For now just redirect to documents index filtered by folder via Livewire state
        return redirect()->route('documents.index')->with('open_folder_id', $folder->id);
    }

    public function approve(Folder $folder): RedirectResponse
    {
        Gate::authorize('approve', Document::class);

        $this->updateFolderTreeStatus($folder, 'approved');

        return back()->with('success', 'Folder and all contained documents approved.');
    }

    public function decline(Folder $folder): RedirectResponse
    {
        Gate::authorize('decline', Document::class);

        $this->updateFolderTreeStatus($folder, 'declined');

        return back()->with('success', 'Folder and all contained documents declined.');
    }

    protected function updateFolderTreeStatus(Folder $folder, string $status): void
    {
        $folder->status = $status;
        $folder->save();

        // Update documents directly inside this folder so that status history,
        // notifications, and OCR behave the same as single-document approvals.
        $documents = Document::where('folder_id', $folder->id)->get();

        foreach ($documents as $document) {
            $document->status = $status;
            $document->save();

            if ($status === 'approved') {
                $document->logAction('approved');
                $document->queueOcrIfNeeded();
            } elseif ($status === 'declined') {
                $document->logAction('declined');
            }
        }

        // Recurse into children
        foreach ($folder->children as $child) {
            $this->updateFolderTreeStatus($child, $status);
        }
    }
}
