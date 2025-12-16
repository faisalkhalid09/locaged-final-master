<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class DocumentMovementController extends Controller
{


    // Store new document movement
    public function store(Request $request)
    {
        Gate::authorize('create', DocumentMovement::class);

        $data = $request->validate([
            'document_id' => 'required|exists:documents,id',
            'movement_type' => 'required|string|in:storage,retrieval,transfer',
            'moved_from_box_id' => 'nullable|exists:boxes,id',
            'moved_to_box_id' => 'required|exists:boxes,id',
        ]);

        $document = Document::findOrFail($data['document_id']);
        $data['moved_from_box_id'] = $data['moved_from_box_id'] ?? $document->box_id;

        if (!$data['moved_from_box_id']) {
            return back()->with('error','Moved from location is empty!');
        }

        if ($data['moved_from_box_id'] === $data['moved_to_box_id']) {
            return back()->with('error','Cannot move to same location');
        }
        
        // Also update document's box_id
        $document->box_id = $data['moved_to_box_id'];
        $document->save();

        $data['moved_by'] = Auth::id();
        $data['moved_at'] = now();
        DocumentMovement::create($data);

        $document->logAction('moved');


        return redirect()->back()->with('success', 'Document moved successfully.');
    }



    // Update movement
    public function update(Request $request, DocumentMovement $documentMovement)
    {
        Gate::authorize('update', $documentMovement);

        $data = $request->validate([
            'document_id' => 'required|exists:documents,id',
            'movement_type' => 'required|string|max:255',
            'moved_from_box_id' => 'nullable|exists:boxes,id',
            'moved_to_box_id' => 'required|exists:boxes,id',
            'moved_at' => 'nullable|date',
        ]);

        $documentMovement->update($data);

        return redirect()->route('document-movements.index')->with('success', 'Document movement updated.');
    }

    // Delete movement
    public function destroy(DocumentMovement $documentMovement)
    {
        Gate::authorize('delete', DocumentMovement::class);

        $documentMovement->delete();

        return redirect()->route('document-movements.index')->with('success', 'Document movement deleted.');
    }
}
