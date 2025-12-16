<?php

namespace App\Livewire;

use App\Models\DocumentVersion;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class DocumentElasticSearch extends Component
{
    public $query = '';
    public $results = [];

    public $filters = [
        'type' => '',
        'creation_start' => '',
        'creation_end' => '',
        'modified_start' => '',
        'modified_end' => '',
        'author' => '',
        'keywords' => '',
        'tags' => '',
    ];


    /**
     * React to changes on the search box and any filter field.
     *
     * This ensures that Creation Date, Keywords, Tags and Author
     * (as well as File Type) immediately affect the live results
     * dropdown under the header search bar.
     */
    public function updated($propertyName)
    {
        // When the main query changes or any of the nested
        // filters (filters.*) change, re-run the search.
        if ($propertyName === 'query' || str_starts_with($propertyName, 'filters.')) {
            $this->searchDocuments();
        }
    }

    public function applyFilters()
    {
        return $this->goToDocuments();
    }

    public function resetFilters()
    {
        $this->filters = [
            'type' => '',
            'creation_start' => '',
            'creation_end' => '',
            'modified_start' => '',
            'modified_end' => '',
            'author' => '',
            'keywords' => '',
            'tags' => '',
        ];

        $this->searchDocuments(); // Optional: refresh results after reset
    }


    public function getActiveFiltersCountProperty()
    {
        return collect($this->filters)->filter(fn($value) => !empty($value))->count();
    }


    private function searchDocuments()
    {
        // Use a simple database query against DocumentVersion + Document title
        // so the header search works even when Elasticsearch / Scout is not
        // configured.
        $term = trim((string) $this->query);
        if (strlen($term) < 2) {
            $this->results = [];
            return;
        }

        $like = '%' . strtolower($term) . '%';

        $searchResults = DocumentVersion::with(['uploadedBy', 'document.tags'])
            ->where(function ($q) use ($like) {
                // Search in document title
                $q->whereHas('document', function ($docQ) use ($like) {
                    $docQ->whereRaw('LOWER(title) LIKE ?', [$like]);
                })
                // Also search in OCR text
                ->orWhereRaw('LOWER(ocr_text) LIKE ?', [$like]);
            })
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        // Apply other filters manually
        $this->results = $searchResults->filter(function ($doc) {
            // FILTER: Type (based on file extension)
            if ($this->filters['type']) {
                $ext = strtolower(pathinfo($doc->file_path, PATHINFO_EXTENSION));

                $typeExtensions = [
                    'pdf' => ['pdf'],
                    'doc' => ['doc', 'docx'],
                    'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'],
                    'excel' => ['xls', 'xlsx', 'csv'],
                    'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'],
                    'audio' => ['mp3', 'wav', 'flac', 'aac', 'ogg', 'm4a'],
                ];

                // If the extension is not in the list of extensions for this type, exclude
                if (!isset($typeExtensions[$this->filters['type']]) || !in_array($ext, $typeExtensions[$this->filters['type']])) {
                    return false;
                }
            }

            // FILTER: Creation Date (based on document.created_at)
            if ($this->filters['creation_start'] && optional($doc->document->created_at)->lt($this->filters['creation_start'])) {
                return false;
            }

            if ($this->filters['creation_end'] && optional($doc->document->created_at)->gt($this->filters['creation_end'])) {
                return false;
            }

            // FILTER: Modified Date (uploaded_at in DocumentVersion)
            if ($this->filters['modified_start'] && optional($doc->updated_at)->lt($this->filters['modified_start'])) {
                return false;
            }

            if ($this->filters['modified_end'] && optional($doc->updated_at)->gt($this->filters['modified_end'])) {
                return false;
            }

            // FILTER: Author
            if ($this->filters['author']) {
                $authorFilter = strtolower($this->filters['author']);

                // $username = strtolower($doc->uploadedBy->username ?? '');
                $fullName = strtolower($doc->uploadedBy->full_name ?? '');
                $email = strtolower($doc->uploadedBy->email ?? '');
                $metadataAuthor = strtolower($doc->metadata['author'] ?? '');

                if (
                    // !str_contains($username, $authorFilter) &&
                    !str_contains($fullName, $authorFilter) &&
                    !str_contains($email, $authorFilter) &&
                    !str_contains($metadataAuthor, $authorFilter)
                ) {
                    return false;
                }
            }

            $filterTags = array_filter(array_map('trim', explode(',', strtolower($this->filters['tags'] ?? ''))));

            // FILTER: Keywords
            if (!empty($this->filters['keywords'])) {
                $filterKeywords = array_filter(array_map('trim', explode(',', strtolower($this->filters['keywords']))));
                $haystack = strtolower(($doc->ocr_text ?? '') . ' ' . ($doc->document->title ?? ''));
                foreach ($filterKeywords as $keyword) {
                    if (stripos($haystack, $keyword) === false) { return false; }
                }
            }

            // FILTER: Tags
            if (!empty($filterTags)) {
                $docTags = $doc->document->tags->pluck('name')->map(fn($t) => strtolower($t))->toArray();
                if (!collect($filterTags)->every(fn($tag) => in_array($tag, $docTags))) { return false; }
            }

            return true;
        })->values();
    }

    public function goToDocuments()
    {
        // Map header filters to DocumentsTable query params
        $fileType = $this->filters['type'] ?? '';

        $params = [
            'search'   => $this->query ?: null,
            'fileType' => $fileType ?: null,
            'dateFrom' => $this->filters['creation_start'] ?: null,
            'dateTo'   => $this->filters['creation_end'] ?: null,
            'author'   => $this->filters['author'] ?: null,
            'keywords' => $this->filters['keywords'] ?: null,
            'tags'     => $this->filters['tags'] ?: null,
        ];

        // Remove nulls
        $params = array_filter($params, function ($v) { return !is_null($v) && $v !== ''; });

        return redirect()->to(route('documents.all', $params));
    }

    public function render()
    {
        return view('livewire.document-elastic-search');
    }
}
