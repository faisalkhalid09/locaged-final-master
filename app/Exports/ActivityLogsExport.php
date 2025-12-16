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

class ActivityLogsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    use DefaultStyles;

    protected int $rowCount = 1; // starts at header
    protected Collection $logs;
    protected string $logType;

    public function __construct(Collection $logs, string $logType = 'documents')
    {
        $this->logs = $logs;
        $this->logType = $logType;
    }

    public function collection(): Collection
    {
        return $this->logs;
    }

    public function headings(): array
    {
        if ($this->logType === 'authentication') {
            return [
                'Date/Time',
                'User',
                'Email',
                'Type',
                'IP Address',
                'User Agent',
            ];
        }

        return [
            'Date/Time',
            'User',
            'Department',
            'Action',
            'Document',
            'IP Address',
        ];
    }

    public function map($log): array
    {
        $this->rowCount++;

        if ($this->logType === 'authentication') {
            return [
                $log->occurred_at?->format('d/m/Y H:i:s') ?? '—',
                $log->user?->full_name ?? 'N/A',
                $log->email ?? 'N/A',
                ucfirst(str_replace('_', ' ', $log->type ?? '')),
                $log->ip_address ?? 'N/A',
                $log->user_agent ?? 'N/A',
            ];
        }

        return [
            $log->occurred_at?->format('d/m/Y H:i:s') ?? '—',
            $log->user?->full_name ?? 'N/A',
            $log->document?->department?->name ?? 'N/A',
            ucfirst(str_replace('_', ' ', $log->action ?? '')),
            $log->document?->title ?? 'N/A',
            $log->ip_address ?? 'N/A',
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

                // Insert header rows above the table (3 rows)
                $sheet->insertNewRowBefore(1, 3);

                // Header section
                $title = $this->logType === 'authentication' 
                    ? 'Authentication Activity Log' 
                    : 'Document Activity Log';
                $sheet->setCellValue('A1', $title);
                $sheet->mergeCells('A1:F1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

                $sheet->setCellValue('A2', 'Generated on: ' . now()->format('d/m/Y H:i'));
                $sheet->setCellValue('A3', 'Total records: ' . ($this->rowCount - 1));

                // Freeze the header row of the data table (now at row 4)
                $sheet->freezePane('A5');

                // Adjust Date/Time column width
                $sheet->getColumnDimension('A')->setWidth(20);
            },
        ];
    }
}
