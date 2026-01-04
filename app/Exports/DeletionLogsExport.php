<?php

namespace App\Exports;

use App\Exports\Concerns\DefaultStyles;
use App\Models\AuditLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class DeletionLogsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    use DefaultStyles;

    protected int $rowCount = 1; // starts at header
    protected ?Collection $filteredData = null;

    public function __construct(?Collection $filteredData = null)
    {
        $this->filteredData = $filteredData;
    }

    public function collection(): Collection
    {
        if ($this->filteredData !== null) {
            return $this->filteredData;
        }

        return AuditLog::with(['user', 'document' => function ($q) {
                $q->withTrashed()->with(['department', 'service.subDepartment']);
            }])
            ->where('action', 'permanently_deleted')
            ->orderByDesc('occurred_at')
            ->get();
    }


    public function headings(): array
    {
        return [
            ui_t('pages.deletion_log.export.document_title'),
            ui_t('pages.deletion_log.export.document_id'),
            ui_t('pages.deletion_log.export.creation_date'),
            ui_t('pages.deletion_log.export.expiration_date'),
            ui_t('pages.deletion_log.export.deleted_on'),
            ui_t('pages.deletion_log.export.deleted_by'),
            ui_t('pages.deletion_log.export.pole'),
            ui_t('pages.deletion_log.export.department'),
            ui_t('pages.deletion_log.export.service'),
        ];
    }

    public function map($log): array
    {
        $this->rowCount++;

        $doc = $log->document;
        $dept = $doc?->department;
        $service = $doc?->service;
        $subDept = $service?->subDepartment;

        return [
            $doc?->title ?? __('(Deleted document)'),
            $log->document_id,
            $doc?->created_at?->format('d/m/Y') ?? '—',
            $doc?->expire_at?->format('d/m/Y') ?? '—',
            $log->occurred_at?->format('d/m/Y H:i') ?? '—',
            $log->user?->full_name ?? 'N/A',
            $dept?->name ?? '—',
            $subDept?->name ?? '—',
            $service?->name ?? '—',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastRow = $this->rowCount;
                $lastCol = 'I';
                $this->applyDefaultSheetStyles($event, $lastRow, $lastCol);

                $sheet = $event->sheet->getDelegate();

                // Insert header rows above the table (3 rows)
                $sheet->insertNewRowBefore(1, 3);

                // Header section
                $sheet->setCellValue('A1', ui_t('pages.deletion_log.export.title'));
                $sheet->mergeCells('A1:I1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

                $sheet->setCellValue('A2', ui_t('pages.deletion_log.export.generated_on') . ' ' . now()->format('d/m/Y H:i'));
                $sheet->setCellValue('A3', ui_t('pages.deletion_log.export.total_deletions') . ' ' . ($this->rowCount - 1));

                // Freeze the header row of the data table (now at row 4)
                $sheet->freezePane('A4');
            },
        ];
    }
}
