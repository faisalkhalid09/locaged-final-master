<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\PhysicalLocation;
use App\Models\Room;
use App\Models\Subcategory;
use App\Models\SubDepartment;
use App\Models\Service;
use App\Models\Tag;
use App\Services\PdfConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DocumentVersionController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Document::class);

        return view('document-versions.index');
    }

    public function byDocument($id)
    {

        $document = Document::with('documentVersions')->findOrFail($id);
        Gate::authorize('view', $document);


        $documentVersions = $document->documentVersions()->paginate(10);


        return view('document-versions.by-document', compact('documentVersions','document'));
    }

    public function create($documentId)
    {
        Gate::authorize('create', Document::class);
        $document = Document::findOrFail($documentId);
        return view('document-versions.create', compact('document'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Document::class);

        $maxFileSizeKb    = (int) config('uploads.max_file_size_kb', 50000);
        $allowedExtensions = config('uploads.allowed_extensions', []);
        $mimesPart         = !empty($allowedExtensions)
            ? '|mimes:' . implode(',', $allowedExtensions)
            : '';

        $validated = $request->validate([
            'document_id' => 'required|exists:documents,id',
            'file' => 'required|file|max:' . $maxFileSizeKb . $mimesPart,
            'ocr_text' => 'nullable|string',
        ]);

        $path = $request->file('file')->store('document_versions');

        $extension = $request->file('file')->getClientOriginalExtension();



        $docVersion = DocumentVersion::create([
            'document_id' => $validated['document_id'],
            'file_path' => $path,
            'file_type' => getFileCategory($extension),
            'ocr_text' => $validated['ocr_text'] ?? null,
            'uploaded_by' => auth()->id(),
            'uploaded_at' => now(),
        ]);

        // Automatically convert Word/Excel versions to PDF right after upload
        // so previews are ready. This mirrors the behavior in DocumentController.
        if (in_array($docVersion->file_type, ['doc', 'excel'], true)) {
            app(PdfConversionService::class)->convertToPdf($docVersion->file_path);
        }

        return redirect()->route('document-versions.index')->with('success', 'Document version uploaded.');
    }


    public function destroy(DocumentVersion $documentVersion)
    {
        Gate::authorize('delete', $documentVersion->document);

        \App\Models\DocumentVersion::withoutSyncingToSearch(function () use ($documentVersion) {
            $documentVersion->delete();
        });

        return redirect()->route('document-versions.index')->with('success', 'Document version archived successfully.');
    }

    public function preview($id)
    {
        try {
            $doc = DocumentVersion::findOrFail($id);
            
            // Load document without global scopes to allow viewing expired documents
            // (needed for destructions page preview)
            $document = Document::withoutGlobalScopes()
                ->where('id', $doc->document_id)
                ->first();
            
            // Check if document is deleted
            if (!$document || $document->trashed()) {
                return redirect()->back()->with('error', 'This document has been deleted and is no longer available.');
            }
            
            Gate::authorize('view', $document);

            // Log the view action
            $document->logAction('viewed', $doc->id);

            if (!Storage::disk('local')->exists($doc->file_path)) {
                return redirect()->back()->with('error', 'The document file has been deleted and is no longer available.');
            }

            // Base file URL served by our app (used for downloads and for non-Office previews)
            $fileUrl = route('documents.versions.file', ['id' => $id]);
            $fileType = $doc->file_type;

            // For Word/Excel, attempt to resolve or create a converted PDF. We
            // keep $fileType as-is ("doc" / "excel") and instead expose a
            // separate $pdfUrl for the Blade view, similar to fullscreen mode.
            $pdfUrl = null;
            if (in_array($fileType, ['doc', 'excel'], true)) {
                $converter = app(PdfConversionService::class);
                $pdfPath   = $converter->convertToPdf($doc->file_path);

                if ($pdfPath) {
                    $pdfUrl = route('documents.versions.pdf', ['id' => $id]);
                }
            }

            // Build lightweight, read-only previews for Excel and Word so we don't
            // depend on external online viewers (which fail on private URLs).
            // NOTE: these are kept for backwards compatibility but are no longer
            // used when a PDF preview is available.
            $excelPreviewRows = null;
            $wordPreviewParagraphs = null;

            // Excel: render a small HTML table from the first sheet (up to A1:Z50)
            if ($doc->file_type === 'excel') {
                try {
                    $absolutePath = Storage::disk('local')->path($doc->file_path);
                    $spreadsheet = IOFactory::load($absolutePath);
                    $sheet = $spreadsheet->getActiveSheet();
                    $excelPreviewRows = $sheet->rangeToArray('A1:Z50', null, true, true, false);
                } catch (\Throwable $e) {
                    $excelPreviewRows = null; // fallback handled in view
                }
            }

            // Word: for .docx files, extract plain text paragraphs from document.xml
            if ($doc->file_type === 'doc') {
                try {
                    $absolutePath = Storage::disk('local')->path($doc->file_path);
                    $lower = strtolower($absolutePath);
                    if (str_ends_with($lower, '.docx') && class_exists('ZipArchive')) {
                        $zip = new \ZipArchive();
                        if ($zip->open($absolutePath) === true) {
                            $xml = $zip->getFromName('word/document.xml');
                            $zip->close();
                            if ($xml !== false) {
                                // Convert paragraph and tab markers into simple text breaks
                                $xml = preg_replace('/<w:p[^>]*>/', "\n", $xml);
                                $xml = preg_replace('/<w:tab[^>]*>/', "\t", $xml);
                                $text = strip_tags($xml);
                                $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
                                $paragraphs = preg_split("/\n+/", trim($text));
                                $paragraphs = array_values(array_filter(array_map('trim', $paragraphs)));
                                // Limit to first 100 paragraphs for performance
                                $wordPreviewParagraphs = array_slice($paragraphs, 0, 100);
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $wordPreviewParagraphs = null;
                }
            }

            // Build navigation across documents awaiting approval.
            // We keep navigation visible even after the current document
            // has been approved, as long as we are in the approval context.
            $isApprovalContext = request()->boolean('approval');
            $prevApprovalUrl = null;
            $nextApprovalUrl = null;
            $prevApprovalTitle = null;
            $nextApprovalTitle = null;

            // For backwards-compatibility, also treat a still-pending document as
            // being in the approval context even if the query param is missing.
            if (! $isApprovalContext && ($document->status ?? null) === 'pending') {
                $isApprovalContext = true;
            }

            if ($isApprovalContext) {
                // All pending documents visible to this user, ordered the same
                // way as on the status page (latest first).
                $pendingIds = Document::where('status', 'pending')
                    ->whereHas('latestVersion')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->pluck('id');

                if ($pendingIds->isNotEmpty()) {
                    $currentDocId = $document->id;
                    $currentIndex = $pendingIds->search($currentDocId);

                    // When the current document is still pending, use its neighbors
                    // inside the pending list.
                    if ($currentIndex !== false) {
                        $prevId = $pendingIds[$currentIndex - 1] ?? null;
                        $nextId = $pendingIds[$currentIndex + 1] ?? null;
                    } else {
                        // When the current document has just been approved/declined,
                        // it will no longer be in the pending list. In that case we
                        // still want to offer a "Next" button to the first pending
                        // document, but no "Prev" so the user cannot navigate back.
                        $prevId = null;
                        $nextId = $pendingIds->first();
                    }

                    if ($prevId) {
                        $prevDoc = Document::with('latestVersion')->find($prevId);
                        if ($prevDoc && $prevDoc->latestVersion) {
                            $routeParams = ['id' => $prevDoc->latestVersion->id];
                            if (request()->boolean('approval')) {
                                $routeParams['approval'] = 1;
                            }
                            $prevApprovalUrl = route('document-versions.preview', $routeParams);
                            $prevApprovalTitle = $prevDoc->title;
                        }
                    }

                    if ($nextId) {
                        $nextDoc = Document::with('latestVersion')->find($nextId);
                        if ($nextDoc && $nextDoc->latestVersion) {
                            $routeParams = ['id' => $nextDoc->latestVersion->id];
                            if (request()->boolean('approval')) {
                                $routeParams['approval'] = 1;
                            }
                            $nextApprovalUrl = route('document-versions.preview', $routeParams);
                            $nextApprovalTitle = $nextDoc->title;
                        }
                    }
                }
            }

            // Filter organization hierarchy by user permissions
            $user = auth()->user();
            $user->refresh();
            $user->loadMissing(['subDepartments', 'services']);

            // Departments from pivot, bypassing Department global scope
            $userDeptIdsRaw = DB::table('department_user')
                ->where('user_id', $user->id)
                ->pluck('department_id');

            $userDepartmentsRaw = Department::withoutGlobalScopes()
                ->whereIn('id', $userDeptIdsRaw)
                ->get();

            if ($user && ($user->hasRole('master') || $user->hasRole('Super Administrator'))) {
                // Privileged users see the full hierarchy
                $userDepartments    = Department::withoutGlobalScopes()->orderBy('name')->get();
                $userSubDepartments = SubDepartment::with('department')->orderBy('name')->get();
                $userServices       = Service::with('subDepartment.department')->orderBy('name')->get();
            } else {
                // Non-admins are restricted to explicit assignments via pivots
                $userDepartments = $userDepartmentsRaw;

                // Sub-departments
                if ($user && $user->hasRole('Department Administrator')) {
                    // Department Admin: all sub-departments in their departments
                    $deptIds = $userDepartments->pluck('id');
                    $userSubDepartments = SubDepartment::whereIn('department_id', $deptIds)->get();
                } else {
                    // Others: explicit assignments via pivot
                    $userSubDepartments = $user->subDepartments;
                }

                // Services
                if ($user && $user->hasRole('Division Chief')) {
                    // Division Chief: all services under their sub-departments
                    $subIds = $userSubDepartments->pluck('id');
                    $userServices = Service::whereIn('sub_department_id', $subIds)->get();
                } elseif ($user && $user->hasRole('Department Administrator')) {
                    // Department Admin: all services under sub-departments in their departments
                    $subIds = $userSubDepartments->pluck('id');
                    $userServices = Service::whereIn('sub_department_id', $subIds)->get();
                } else {
                    // Others (service roles): explicit service assignments
                    $userServices = $user->services;
                }
            }

            // Categories are already scoped by the Category model (service hierarchy + roles)
            $categories = Category::orderBy('name')->get();
            $subcategories = Subcategory::with('category')->orderBy('name')->get();

            // Load hierarchical structure for box selection
            $rooms = Room::with(['rows.shelves.boxes'])->get();
            $tags = Tag::all(); // Tags are generally accessible

            return view('document-versions.preview', [
                'fileUrl'              => $fileUrl,
                'fileType'             => $fileType,
                'pdfUrl'               => $pdfUrl,
                'excelPreviewRows'     => $excelPreviewRows,
                'wordPreviewParagraphs'=> $wordPreviewParagraphs,
                'doc'                  => $doc,
                'document'             => $document,  // Explicitly pass document (bypasses global scopes)
                'categories'         => $categories,
                'subcategories'      => $subcategories,
                'userDepartments'    => $userDepartments,
                'userSubDepartments' => $userSubDepartments,
                'userServices'       => $userServices,
                'rooms'              => $rooms,
                'tags'               => $tags,
                // Approval navigation
                'isApprovalContext'  => $isApprovalContext,
                'prevApprovalUrl'    => $prevApprovalUrl,
                'nextApprovalUrl'    => $nextApprovalUrl,
                'prevApprovalTitle'  => $prevApprovalTitle,
                'nextApprovalTitle'  => $nextApprovalTitle,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'This document has been deleted and is no longer available.');
        }
    }

    public function viewOcr($id)
    {
        try {
            $doc = DocumentVersion::findOrFail($id);
            
            // Check if document is deleted
            if (!$doc->document || $doc->document->trashed()) {
                return redirect()->back()->with('error', 'This document has been deleted and is no longer available.');
            }
            
            Gate::authorize('view', $doc->document);
            Gate::authorize('viewAny', \App\Models\OcrJob::class);

            // Log the view action
            $doc->document->logAction('viewed_ocr', $doc->id);

            if (!Storage::disk('local')->exists($doc->file_path)) {
                return redirect()->back()->with('error', 'The document file has been deleted and is no longer available.');
            }

            $fileUrl = route('documents.versions.file', ['id' => $id]);

            // Reuse the same lightweight previews for OCR view when needed
            $excelPreviewRows = null;
            $wordPreviewParagraphs = null;

            if ($doc->file_type === 'excel') {
                try {
                    $absolutePath = Storage::disk('local')->path($doc->file_path);
                    $spreadsheet = IOFactory::load($absolutePath);
                    $sheet = $spreadsheet->getActiveSheet();
                    $excelPreviewRows = $sheet->rangeToArray('A1:Z50', null, true, true, false);
                } catch (\Throwable $e) {
                    $excelPreviewRows = null;
                }
            }

            if ($doc->file_type === 'doc') {
                try {
                    $absolutePath = Storage::disk('local')->path($doc->file_path);
                    $lower = strtolower($absolutePath);
                    if (str_ends_with($lower, '.docx') && class_exists('ZipArchive')) {
                        $zip = new \ZipArchive();
                        if ($zip->open($absolutePath) === true) {
                            $xml = $zip->getFromName('word/document.xml');
                            $zip->close();
                            if ($xml !== false) {
                                $xml = preg_replace('/<w:p[^>]*>/', "\n", $xml);
                                $xml = preg_replace('/<w:tab[^>]*>/', "\t", $xml);
                                $text = strip_tags($xml);
                                $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
                                $paragraphs = preg_split("/\n+/", trim($text));
                                $paragraphs = array_values(array_filter(array_map('trim', $paragraphs)));
                                $wordPreviewParagraphs = array_slice($paragraphs, 0, 100);
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $wordPreviewParagraphs = null;
                }
            }

            return view('document-versions.view-ocr', [
                'fileUrl'              => $fileUrl,
                'fileType'             => $doc->file_type,
                'excelPreviewRows'     => $excelPreviewRows,
                'wordPreviewParagraphs'=> $wordPreviewParagraphs,
                'doc'                  => $doc,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'This document has been deleted and is no longer available.');
        }
    }

    /**
     * Display document in fullscreen mode (no metadata, just the file)
     */
    public function viewFullscreen($id)
    {
        try {
            $doc = DocumentVersion::findOrFail($id);
            
            // Check if document is deleted
            if (!$doc->document || $doc->document->trashed()) {
                return redirect()->back()->with('error', 'This document has been deleted and is no longer available.');
            }
            
            Gate::authorize('view', $doc->document);

            // Log the view action
            $doc->document->logAction('viewed', $doc->id);

            if (!Storage::disk('local')->exists($doc->file_path)) {
                return redirect()->back()->with('error', 'The document file has been deleted and is no longer available.');
            }

            // Base file URL
            $fileUrl  = route('documents.versions.file', ['id' => $id]);
            $fileType = $doc->file_type;

            // For Word/Excel files, ensure we have a PDF conversion so that
            // the fullscreen preview can always render a PDF when possible.
            $pdfUrl = null;
            if (in_array($fileType, ['doc', 'excel'], true)) {
                $converter = app(PdfConversionService::class);
                $pdfPath   = $converter->convertToPdf($doc->file_path);

                if ($pdfPath) {
                    $pdfUrl = route('documents.versions.pdf', ['id' => $id]);
                }
            }

            // Build navigation for document browsing (skip expired documents)
            $prevDocUrl = null;
            $nextDocUrl = null;
            $prevDocTitle = null;
            $nextDocTitle = null;

            // Get all non-expired documents visible to the user, ordered by latest first
            $nonExpiredIds = Document::where(function($query) {
                    $query->where('is_expired', false)
                          ->orWhereNull('is_expired');
                })
                ->whereHas('latestVersion')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->pluck('id');

            if ($nonExpiredIds->isNotEmpty()) {
                $currentDocId = $doc->document->id;
                $currentIndex = $nonExpiredIds->search($currentDocId);

                if ($currentIndex !== false) {
                    $prevId = $nonExpiredIds[$currentIndex - 1] ?? null;
                    $nextId = $nonExpiredIds[$currentIndex + 1] ?? null;

                    if ($prevId) {
                        $prevDoc = Document::with('latestVersion')->find($prevId);
                        if ($prevDoc && $prevDoc->latestVersion) {
                            $prevDocUrl = route('document-versions.fullscreen', ['id' => $prevDoc->latestVersion->id]);
                            $prevDocTitle = $prevDoc->title;
                        }
                    }

                    if ($nextId) {
                        $nextDoc = Document::with('latestVersion')->find($nextId);
                        if ($nextDoc && $nextDoc->latestVersion) {
                            $nextDocUrl = route('document-versions.fullscreen', ['id' => $nextDoc->latestVersion->id]);
                            $nextDocTitle = $nextDoc->title;
                        }
                    }
                }
            }

            return view('document-versions.fullscreen', [
                'fileUrl'       => $fileUrl,
                'pdfUrl'        => $pdfUrl,
                'fileType'      => $fileType,
                'doc'           => $doc,
                'prevDocUrl'    => $prevDocUrl,
                'nextDocUrl'    => $nextDocUrl,
                'prevDocTitle'  => $prevDocTitle,
                'nextDocTitle'  => $nextDocTitle,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'This document has been deleted and is no longer available.');
        }
    }

    /**
     * Get the path where PDF conversion should be stored
     */
    private function getPdfConversionPath($originalPath)
    {
        $pathInfo = pathinfo($originalPath);
        return $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.pdf';
    }

    public function updateOcr($id,Request $request)
    {
        $request->validate([
            'ocr_text' => 'required|string'
        ]);


        $doc = DocumentVersion::findOrFail($id);
        Gate::authorize('update', $doc->document);
        Gate::authorize('viewAny', \App\Models\OcrJob::class);


        $doc->update([
            'ocr_text' => $request->get('ocr_text')
        ]);

        return back()->with('status', 'ocr-text-updated');

    }

    public static function getFile($id)
    {
        $doc = DocumentVersion::findOrFail($id);
        
        // Load document without global scopes to allow viewing expired documents
        $document = Document::withoutGlobalScopes()
            ->where('id', $doc->document_id)
            ->first();
        
        if (!$document) {
            abort(404, 'Document not found.');
        }
        
        Gate::authorize('view', $document);

        // Log the download action
        $document->logAction('downloaded', $doc->id);


        if (!Storage::disk('local')->exists($doc->file_path)) {
            abort(404, 'File not found.');
        }

        return response()->file(
            Storage::disk('local')->path($doc->file_path)
        );
    }

    public static function downloadFile($id)
    {

        $doc = DocumentVersion::findOrFail($id);
        Gate::authorize('view', $doc->document);

        // Log the download action
        $doc->document->logAction('downloaded', $doc->id);

        if (!Storage::disk('local')->exists($doc->file_path)) {
            abort(404, 'File not found.');
        }

        return response()->download(
            Storage::disk('local')->path($doc->file_path));
    }

    /**
     * Serve converted PDF for Word/Excel files
     */
    public static function getPdf($id)
    {
        $doc = DocumentVersion::findOrFail($id);
        Gate::authorize('view', $doc->document);

        $pdfPath = (new self())->getPdfConversionPath($doc->file_path);

        if (!Storage::disk('local')->exists($pdfPath)) {
            abort(404, 'PDF conversion not found.');
        }

        return response()->file(
            Storage::disk('local')->path($pdfPath)
        );
    }
}
