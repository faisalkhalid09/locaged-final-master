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
                __('Date/Time'),
                __('User'),
                __('Email'),
                __('Type'),
                __('IP Address'),
                __('User Agent'),
            ];
        }

        return [
            __('Date/Time'),
            __('User'),
            __('Department'),
            __('Action'),
            __('Document'),
            __('IP Address'),
        ];
    }

    public function map($log): array
    {
        $this->rowCount++;

        if ($this->logType === 'authentication') {
            return [
                $log->occurred_at?->format('d/m/Y H:i:s') ?? '—',
                $log->user?->full_name ?? __('N/A'),
                $log->email ?? __('N/A'),
                $this->translateAction($log->type ?? ''),
                $log->ip_address ?? __('N/A'),
                $log->user_agent ?? __('N/A'),
            ];
        }

        return [
            $log->occurred_at?->format('d/m/Y H:i:s') ?? '—',
            $log->user?->full_name ?? __('N/A'),
            $log->document?->department?->name ?? __('N/A'),
            $this->translateAction($log->action ?? ''),
            $log->document?->title ?? __('(Deleted document)'),
            $log->ip_address ?? __('N/A'),
        ];
    }

    /**
     * Translate action/type strings
     */
    protected function translateAction(string $action): string
    {
        if (empty($action)) {
            return __('N/A');
        }

        // Common action translations
        $translations = [
            'permanently_deleted' => __('Permanently deleted'),
            'created' => __('Created'),
            'updated' => __('Updated'),
            'deleted' => __('Deleted'),
            'restored' => __('Restored'),
            'viewed' => __('Viewed'),
            'downloaded' => __('Downloaded'),
            'uploaded' => __('Uploaded'),
            'approved' => __('Approved'),
            'rejected' => __('Rejected'),
            'postponed' => __('Postponed'),
            'archived' => __('Archived'),
            'login' => __('Login'),
            'logout' => __('Logout'),
            'failed_login' => __('Failed login'),
        ];

        // Return translation if exists, otherwise format the action nicely
        return $translations[$action] ?? __(ucfirst(str_replace('_', ' ', $action)));
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
                    ? __('Authentication Activity Log') 
                    : __('Document Activity Log');
                $sheet->setCellValue('A1', $title);
                $sheet->mergeCells('A1:F1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

                $sheet->setCellValue('A2', __('Generated on:') . ' ' . now()->format('d/m/Y H:i'));
                $sheet->setCellValue('A3', __('Total records:') . ' ' . ($this->rowCount - 1));

                // Freeze the header row of the data table (now at row 4)
                $sheet->freezePane('A5');

                // Adjust Date/Time column width
                $sheet->getColumnDimension('A')->setWidth(20);
            },
        ];
    }
}
