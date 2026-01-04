<?php

namespace App\Exports;

use App\Exports\Concerns\DefaultStyles;
use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class DestructionRequestsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    use DefaultStyles;

    protected int $rowCount = 1;

    /**
     * Query expired documents - same logic as the index method in controller.
     * Uses withoutGlobalScopes() because Document model has a global scope
     * that hides expired documents from normal queries.
     */
    public function query(): Builder
    {
        $user = auth()->user();
        $query = Document::withoutGlobalScopes()
            ->with(['latestVersion', 'createdBy'])
            ->whereNotNull('expire_at')
            ->where('expire_at', '<=', now())
            ->whereNull('deleted_at');
        
        // Department Administrator: only export expired documents from their departments
        // Admin/Super Admin: export all expired documents from all departments
        $isDeptAdmin = $user && ($user->hasRole('Department Administrator') || $user->hasRole('Admin de pole'));
        $isAdmin = $user && $user->hasRole(['master', 'Super Administrator', 'super administrator']);
        
        if ($isDeptAdmin && !$isAdmin) {
            $deptIds = $user->departments?->pluck('id') ?? collect();
            $query->whereIn('documents.department_id', $deptIds->all());
        }
        
        return $query->orderByDesc('expire_at');
    }

    public function headings(): array
    {
        return [
            ui_t('pages.destructions.document_name'),
            ui_t('pages.destructions.author'),
            ui_t('pages.destructions.created_by'),
            ui_t('pages.destructions.creation_date'),
            ui_t('pages.destructions.expiration_date'),
            ui_t('pages.destructions.status'),
        ];
    }

    public function map($doc): array
    {
        $this->rowCount++;

        // Get author from metadata if available
        $author = $doc->metadata['author'] ?? '—';
        
        // Get created by user's full name
        $createdBy = $doc->createdBy?->full_name ?? '—';
        
        // Format dates
        $creationDate = $doc->created_at?->format('Y-m-d') ?? '—';
        $expirationDate = $doc->expire_at?->format('Y-m-d') ?? '—';
        
        // Status is always "Expired" for documents in this queue
        $statusLabel = ui_t('pages.destructions.status_values.expired');

        return [
            $doc->title ?? '—',
            $author,
            $createdBy,
            $creationDate,
            $expirationDate,
            $statusLabel,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastRow = $this->rowCount;
                $lastCol = 'F';
                $this->applyDefaultSheetStyles($event, $lastRow, $lastCol);
                $sheet = $event->sheet->getDelegate();
                $sheet->insertNewRowBefore(1, 1);
                $sheet->setCellValue('A1', ui_t('pages.destructions.title'));
                $sheet->mergeCells('A1:' . $lastCol . '1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getRowDimension(2)->setRowHeight(22);
            },
        ];
    }
}
