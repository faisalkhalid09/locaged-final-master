<?php

namespace App\Exports;

use App\Exports\Concerns\DefaultStyles;
use App\Http\Controllers\ReportsController;
use App\Models\Document;
use App\Models\DocumentStatusHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class DocumentsReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    use DefaultStyles;

    protected Request $request;
    protected int $rowCount = 1; // starts at header

    /** @var array<string,mixed>|null */
    protected ?array $stats = null;

    protected ?int $totalDocuments = null;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    protected function baseQuery(): Builder
    {
        $query = Document::query()
            ->with([
                'subcategory',
                'category',
                'department',
                'service',
                'box.shelf.row.room',
                'createdBy',
                'latestVersion',
            ]);

        $r = $this->request;

        // Room / physical location
        if ($r->filled('room')) {
            $room = \App\Models\Room::where('name', $r->room)->first();
            if ($room) {
                $boxIds = \App\Models\Box::whereHas('shelf.row.room', function ($q) use ($room) {
                    $q->where('id', $room->id);
                })->pluck('id');
                $query->whereIn('box_id', $boxIds);
            }
        }

        // Department
        if ($r->filled('department_id')) {
            $query->where('department_id', $r->department_id);
        }

        // Creator (super admins only)
        if ($r->filled('user_id') && auth()->user()?->hasRole(['master', 'super_admin', 'super administrator'])) {
            $query->where('created_by', $r->user_id);
        }

        // Year shortcut
        if ($r->filled('year')) {
            $query->whereYear('documents.created_at', $r->year);
        }

        // Date range
        if ($r->filled('date_from')) {
            $query->whereDate('documents.created_at', '>=', $r->date_from);
        }
        if ($r->filled('date_to')) {
            $query->whereDate('documents.created_at', '<=', $r->date_to);
        }

        // Category (by category_id) and subcategory
        if ($r->filled('category_id')) {
            $query->where('category_id', $r->category_id);
        }
        if ($r->filled('subcategory_id')) {
            $query->where('subcategory_id', $r->subcategory_id);
        }

        // Service (for dept/service filtering)
        if ($r->filled('service_id')) {
            $query->where('service_id', $r->service_id);
        }

        // Status
        if ($r->filled('status') && $r->status !== 'all') {
            $query->where('status', $r->status);
        }

        // Simple title search
        if ($r->filled('search')) {
            $query->where('title', 'like', '%' . $r->search . '%');
        }

        return $query;
    }

    public function query(): Builder
    {
        return $this->baseQuery()
            ->orderBy('documents.created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Titre',
            'Catégorie',
            'Structure',
            'Service',
            'Statut',
            'Date de création',
            "Date d'expiration",
            'Créé par',
            'Dernier valideur',
            'Taille du fichier',
        ];
    }

    public function map($doc): array
    {
        $this->rowCount++;

        // Category name (direct category, then via subcategory)
        $categoryName = $doc->category?->name
            ?? $doc->subcategory?->category?->name
            ?? optional($doc->subcategory)->name
            ?? 'N/A';

        // French label for status
        $statusMap = [
            'draft' => 'Brouillon',
            'pending' => 'En attente',
            'approved' => 'Approuvé',
            'declined' => 'Refusé',
            'archived' => 'Archivé',
            'destroyed' => 'Détruit',
            'destruction_pending' => 'Destruction en attente',
        ];
        $statusKey = (string) $doc->status;
        $statusLabel = $statusMap[$statusKey] ?? ucfirst($statusKey ?: 'inconnu');

        // Last approver (last history entry where to_status = approved)
        $lastApprover = DocumentStatusHistory::where('document_id', $doc->id)
            ->where('to_status', 'approved')
            ->orderByDesc('changed_at')
            ->with('changedBy')
            ->first();

        $lastApproverName = $lastApprover?->changedBy?->full_name ?? 'N/A';

        // File size from latest version if available
        $fileSizeDisplay = 'N/A';
        if ($doc->latestVersion && $doc->latestVersion->file_path) {
            try {
                $sizeBytes = Storage::size($doc->latestVersion->file_path);
                if ($sizeBytes > 0) {
                    $sizeMb = $sizeBytes / (1024 * 1024);
                    $fileSizeDisplay = number_format($sizeMb, 2, ',', ' ') . ' Mo';
                }
            } catch (\Throwable $e) {
                $fileSizeDisplay = 'N/A';
            }
        }

        return [
            $doc->title,
            $categoryName,
            optional($doc->department)->name ?? 'N/A',
            optional($doc->service)->name ?? 'N/A',
            $statusLabel,
            optional($doc->created_at)?->format('d/m/Y H:i'),
            optional($doc->expire_at)?->format('d/m/Y'),
            optional($doc->createdBy)->full_name ?? 'N/A',
            $lastApproverName,
            $fileSizeDisplay,
        ];
    }

    protected function ensureStatisticsLoaded(): void
    {
        if ($this->stats !== null) {
            return;
        }

        // Re-use the existing statistics logic from ReportsController for
        // consistency between the web dashboard and the Excel export.
        $controller = app(ReportsController::class);
        $this->stats = $controller->getStatistics($this->request);
        $this->totalDocuments = $this->stats['total_documents'] ?? null;
    }

    protected function buildFiltersSummary(): string
    {
        $r = $this->request;
        $parts = [];

        if ($r->filled('date_from') || $r->filled('date_to')) {
            $from = $r->date_from ? date('d/m/Y', strtotime($r->date_from)) : '...';
            $to = $r->date_to ? date('d/m/Y', strtotime($r->date_to)) : '...';
            $parts[] = "Période={$from} → {$to}";
        } elseif ($r->filled('year')) {
            $parts[] = 'Année=' . $r->year;
        }

        if ($r->filled('department_id')) {
            $parts[] = 'Structure ID=' . $r->department_id;
        }
        if ($r->filled('service_id')) {
            $parts[] = 'Service ID=' . $r->service_id;
        }
        if ($r->filled('category_id')) {
            $parts[] = 'Catégorie ID=' . $r->category_id;
        }
        if ($r->filled('status') && $r->status !== 'all') {
            $parts[] = 'Statut=' . $r->status;
        }
        if ($r->filled('file_type')) {
            $parts[] = 'Type de fichier=' . $r->file_type;
        }
        if ($r->filled('user_id')) {
            $parts[] = 'Créateur ID=' . $r->user_id;
        }

        return $parts ? implode(' ; ', $parts) : 'Aucun filtre (tous les documents)';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->ensureStatisticsLoaded();

                $lastRow = $this->rowCount;
                $lastCol = 'J';
                $this->applyDefaultSheetStyles($event, $lastRow, $lastCol);

                $sheet = $event->sheet->getDelegate();

                // Insert header rows above the table (5 rows)
                $sheet->insertNewRowBefore(1, 5);

                // Header section
                $sheet->setCellValue('A1', 'Rapport sur les documents');
                $sheet->mergeCells('A1:J1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

                $sheet->setCellValue('A2', 'Généré le : ' . now()->format('d/m/Y H:i'));
                $sheet->setCellValue('A3', 'Filtres actifs : ' . $this->buildFiltersSummary());

                $total = $this->totalDocuments ?? ($this->rowCount - 1);
                $sheet->setCellValue('A4', 'Total des documents : ' . $total);

                // Freeze the header row of the data table (now at row 6)
                $sheet->freezePane('A6');
            },
        ];
    }
}

