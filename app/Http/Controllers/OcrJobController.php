<?php

namespace App\Http\Controllers;

use App\Models\OcrJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OcrJobController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', OcrJob::class);

        $ocrJobs = OcrJob::with([
            'documentVersion' => function ($query) {
                $query->withTrashed()->with([
                    'document' => function ($query) {
                        $query->withTrashed();
                    }
                ]);
            }
        ])->latest()->paginate(10);

        return view('ocr_jobs.index', compact('ocrJobs'));
    }


    public function store(Request $request)
    {
        Gate::authorize('create', OcrJob::class);

        $validated = $request->validate([
            'document_version_id' => 'required|exists:document_versions,id',
            'status' => 'required|string|in:queued,processing,completed,failed',
            'queued_at' => 'nullable|date',
            'processed_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'error_message' => 'nullable|string',
        ]);

        OcrJob::create($validated);

        return redirect()->route('ocr-jobs.index')->with('success', 'OCR Job created successfully.');
    }



    public function update(Request $request, OcrJob $ocrJob)
    {
        Gate::authorize('update', $ocrJob);

        $validated = $request->validate([
            'document_version_id' => 'required|exists:document_versions,id',
            'status' => 'required|string|in:queued,processing,completed,failed',
            'queued_at' => 'nullable|date',
            'processed_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'error_message' => 'nullable|string',
        ]);

        $ocrJob->update($validated);

        return redirect()->route('ocr-jobs.index')->with('success', 'OCR Job updated successfully.');
    }

    public function destroy(OcrJob $ocrJob)
    {
        Gate::authorize('delete', $ocrJob);

        $ocrJob->delete();

        return redirect()->route('ocr-jobs.index')->with('success', 'OCR Job deleted successfully.');
    }
}
