<?php

namespace App\Livewire;

use App\Models\Document;
use App\Models\DocumentMovement;
use App\Models\PhysicalLocation;
use App\Services\DocumentSearchService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use ZipArchive;

class DocumentsByCategoryTable extends Component
{
    use WithPagination;

    public $filterId; // ID of either category or subcategory
    public $isCategory = false; // true if filtering by category, false if filtering by subcategory
    public $contextLabel = null; // Optional label like category or subcategory name for UI headings

    public $search = '';
    public $status = '';
    public $fileType = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $room = '';
    public $author = '';
    public $keywords = '';
    public $tags = '';
    public $favoritesOnly = false;
    public $documentId = null; // Filter by specific document ID
    public $boxId = ''; // Filter by box ID (physical location)
    public $showExpired = false; // Show expired documents (from dashboard All Documents card)
    public $pageTitle = null; // Heading to display (from dashboard cards)

    public $documentsIds = [];
    public $checkedDocuments = []; // IDs of selected documents
    public $selectAll = false;
    public $page = 1;
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'fileType' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'room' => ['except' => ''],
        'author' => ['except' => ''],
        'keywords' => ['except' => ''],
        'tags' => ['except' => ''],
        'favoritesOnly' => ['except' => false],
        'perPage' => ['except' => 10],
        'boxId' => ['except' => '', 'as' => 'box_id'],
        'documentId' => ['except' => null, 'as' => 'document_id'],
        'showExpired' => ['except' => false, 'as' => 'show_expired'],
        'pageTitle' => ['except' => null, 'as' => 'page_title'],
    ];

    public function mount($filterId = null, $isCategory = false, $contextLabel = null): void
    {
        $this->filterId = $filterId;
        $this->isCategory = $isCategory;
        $this->contextLabel = $contextLabel;
    }

    public function updated($field)
    {
        // Reset to first page when any filter changes
        if (in_array($field, ['search', 'status', 'fileType', 'dateFrom', 'dateTo', 'room', 'author', 'keywords', 'tags', 'boxId', 'favoritesOnly', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->status = '';
        $this->fileType = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->room = '';
        $this->author = '';
        $this->keywords = '';
        $this->tags = '';
        $this->boxId = '';
        $this->favoritesOnly = false;
        $this->perPage = 10; // Reset to default

        $this->resetPage();
    }



    public function updatedSelectAll($value)
    {

        if ($value) {
            $this->checkedDocuments = $this->documentsIds;
        } else {
            $this->checkedDocuments = [];
        }
    }

    public function downloadSelected()
    {
        if (empty($this->checkedDocuments)) {
            session()->flash('error', 'No documents selected for download.');
            return null;
        }

        // Get all selected documents
        $documents = Document::whereIn('id', $this->checkedDocuments)->get();

        if ($documents->isEmpty()) {
            session()->flash('error', 'Selected documents not found.');
            return null;
        }

        // Create a temporary ZIP file
        $zipFileName = 'documents_' . now()->timestamp . '.zip';
        $zipFilePath = storage_path('app/public/' . $zipFileName);

        $zip = new ZipArchive;

        if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
            foreach ($documents as $doc) {
                if (!empty($doc->latestVersion) && Storage::exists($doc->latestVersion->file_path)) {
                    $zip->addFile(
                        Storage::path($doc->latestVersion->file_path),
                        basename($doc->latestVersion->file_path)
                    );
                }
            }

            // Check if any files were added
            if ($zip->numFiles === 0) {
                $zip->close();
                session()->flash('error', 'No files found for the selected documents.');
                return back();
            }

            $zip->close();
        }

        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }


    public function render()
    {
        // Base documents query using the database only (no Elasticsearch).
        // This makes search/filtering always work even if the search engine
        // is not configured.
        $documentsQuery = Document::with([
            'subcategory', 'department', 'box.shelf.row.room', 'createdBy', 'latestVersion', 'auditLogs.user'
        ]);

        // Note: Expired documents are now always shown in the listing
        // The is_expired flag is maintained for visual indicators only

        // Handle category/subcategory filter
        if ($this->filterId) {
            $documentsQuery->when($this->filterId, function ($q) {
                if ($this->isCategory) {
                    // Filter directly by category_id so documents linked only to the category
                    // (without a subcategory) are also included.
                    $q->where('category_id', $this->filterId);
                } else {
                    // If it's a subcategory, filter directly
                    $q->where('subcategory_id', $this->filterId);
                }
            });
        }

        // Status filter
        $documentsQuery->when($this->status && $this->status !== 'all', function ($q) {
            $q->where('status', $this->status);
        });

        // Box filter (physical location)
        $documentsQuery->when($this->boxId, function ($q) {
            $q->where('box_id', $this->boxId);
        });

        // File type filter via latest version
        $documentsQuery->when($this->fileType, function ($q) {
            $q->whereHas('latestVersion', function ($sub) {
                $sub->where('file_type', $this->fileType);
            });
        });

        // Room filter (convert to box_ids)
        if ($this->room) {
            $roomModel = \App\Models\Room::where('name', $this->room)->first();
            if ($roomModel) {
                $boxIds = \App\Models\Box::whereHas('shelf.row.room', function ($q) use ($roomModel) {
                    $q->where('id', $roomModel->id);
                })->pluck('id');
                $documentsQuery->whereIn('box_id', $boxIds);
            }
        }

        // Specific document filter (from audit page)
        $documentsQuery->when($this->documentId, function ($q) {
            $q->where('id', $this->documentId);
        });

        // Favorites filter
        $documentsQuery->when($this->favoritesOnly, function ($q) {
            $q->whereHas('favoritedByUsers', function ($q2) {
                $q2->where('user_id', auth()->id());
            });
        });

        // Date range filters
        $documentsQuery->when($this->dateFrom, function ($q) {
            $q->whereDate('created_at', '>=', $this->dateFrom);
        });

        $documentsQuery->when($this->dateTo, function ($q) {
            $q->whereDate('created_at', '<=', $this->dateTo);
        });

        // Full-text search: title AND OCR content from latest version
        if (! empty($this->search)) {
            $searchTerm = '%' . strtolower($this->search) . '%';
            $documentsQuery->where(function ($q) use ($searchTerm) {
                // Search in document title
                $q->whereRaw('LOWER(title) LIKE ?', [$searchTerm])
                  // Also search in OCR text from the latest version
                  ->orWhereHas('latestVersion', function ($sub) use ($searchTerm) {
                      $sub->whereRaw('LOWER(ocr_text) LIKE ?', [$searchTerm]);
                  });
            });
        }

        // Apply ordering - latest first (same order as displayed in the table)
        $documentsQuery->latest()->orderBy('id', 'desc');
        
        // Get ALL filtered document IDs for navigation BEFORE pagination
        // This ensures navigation works across all pages in the correct order
        $this->documentsIds = (clone $documentsQuery)->pluck('id')->toArray();

        // Now paginate for display
        $documents = $documentsQuery->paginate($this->perPage);

        $movements = DocumentMovement::all();
        // Load rooms for move modal hierarchical selection
        $rooms = \App\Models\Room::with(['rows.shelves.boxes'])->get();

        return view('livewire.documents-by-category-table', [
            'documents' => $documents,
            'movements' => $movements,
            'rooms' => $rooms,
            'documentsIds' => $this->documentsIds,
        ]);
    }

    public function toggleFavorite(int $documentId): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }
        $isFav = $user->favoriteDocuments()->where('document_id', $documentId)->exists();
        if ($isFav) {
            $user->favoriteDocuments()->detach($documentId);
        } else {
            $user->favoriteDocuments()->attach($documentId);
        }
        $this->resetPage();
    }

    public function bulkDelete()
    {
        // Only master role can perform bulk delete
        if (!auth()->user()->hasRole('master')) {
            session()->flash('error', 'You do not have permission to perform bulk delete.');
            return;
        }

        if (empty($this->checkedDocuments)) {
            session()->flash('error', 'No documents selected for deletion.');
            return;
        }

        // Get all selected documents with their versions
        $documents = Document::with('documentVersions')->whereIn('id', $this->checkedDocuments)->get();

        if ($documents->isEmpty()) {
            session()->flash('error', 'Selected documents not found.');
            return;
        }

        $deletedCount = 0;
        $errors = [];

        try {
            DB::beginTransaction();

            foreach ($documents as $document) {
                try {
                    // Log the action before deletion (same as individual delete)
                    $document->logAction('permanently_deleted');

                    // Remove from search index and delete all document versions and their files
                    foreach ($document->documentVersions as $version) {
                        // Remove from search index first
                        $version->unsearchable();

                        // Delete the file if it exists
                        if ($version->file_path && Storage::exists($version->file_path)) {
                            Storage::delete($version->file_path);
                        }
                        $version->delete();
                    }

                    // Delete the document itself
                    $document->delete();
                    $deletedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to delete document '{$document->title}': " . $e->getMessage();
                }
            }

            DB::commit();

            // Clear selected documents
            $this->checkedDocuments = [];
            $this->selectAll = false;

            if ($deletedCount > 0) {
                $message = "Successfully permanently deleted {$deletedCount} document(s).";
                if (!empty($errors)) {
                    $message .= " Errors: " . implode(', ', $errors);
                }
                session()->flash('success', $message);
            }

            if (!empty($errors) && $deletedCount === 0) {
                session()->flash('error', 'Failed to delete documents: ' . implode(', ', $errors));
            }

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'An error occurred during bulk delete: ' . $e->getMessage());
        }

        $this->resetPage();
    }
}
