<?php

namespace App\Livewire;

use App\Models\DocumentVersion;
use App\Services\DocumentSearchService;
use Livewire\Component;
use Livewire\WithPagination;

class DocumentsVersionTable extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $fileType = '';
    public $dateFrom = '';
    public $dateTo = '';

    public $documentsIds = [];
    public $checkedDocuments = []; // IDs of selected documents
    public $selectAll = false;
    public $page = 1;

    public ?int $documentId = null;


    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'fileType' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function mount(?int $documentId = null)
    {
        $this->documentId = $documentId;
    }

    public function updated($field)
    {
        // Reset to first page when any filter changes
        if (in_array($field, ['search', 'status', 'fileType', 'dateFrom', 'dateTo'])) {
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



    public function render()
    {
        // Prepare filters for DocumentSearchService
        $filters = [
            'status' => $this->status,
            'file_type' => $this->fileType,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ];

        // Handle document ID filter
        if ($this->documentId) {
            $filters['document_id'] = $this->documentId;
        }

        // Use Elasticsearch for search, fallback to database for no search
        if (!empty($this->search)) {
            $documentVersions = DocumentSearchService::searchVersions($this->search, $filters, 10, $this->page);
        } else {
            // Fallback to database query when no search term
            $documentVersions = DocumentVersion::with(['document'])
                ->when($this->documentId, fn($q) => 
                    $q->whereHas('document', fn($subQ) => 
                        $subQ->where('id', $this->documentId)
                    )
                )
                ->when($this->status, fn($q) =>
                    $q->whereHas('document', fn($subQ) =>
                        $subQ->where('status', $this->status)
                    )
                )
                ->when($this->fileType, fn($q) =>
                    $q->where('file_type', $this->fileType)
                )
                ->when($this->dateFrom, fn($q) =>
                    $q->whereDate('created_at', '>=', $this->dateFrom)
                )
                ->when($this->dateTo, fn($q) =>
                    $q->whereDate('created_at', '<=', $this->dateTo)
                )
                ->latest()
                ->paginate(10);
        }

        $this->documentsIds = $documentVersions->pluck('id')->toArray();

        return view('livewire.documents-version-table', compact('documentVersions'));
    }
}
