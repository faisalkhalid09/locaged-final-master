<?php

namespace App\Exports;

use App\Exports\Concerns\DefaultStyles;
use App\Models\DocumentDestructionRequest;
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

    public function query(): Builder
    {
        return DocumentDestructionRequest::query()
            ->with(['document.latestVersion', 'requestedBy'])
            ->orderBy('id');
    }

    public function headings(): array
    {
        return ['Document Title', 'File Name', 'Status', 'Requested By', 'Requested At'];
    }

    public function map($req): array
    {
        $this->rowCount++;
        $doc = $req->document;
        $latestPath = $doc?->latestVersion?->file_path;
        $fileName = $latestPath ? basename($latestPath) : '';
        return [
            $doc?->title ?? '',
            $fileName,
            $req->status,
            $req->requestedBy?->full_name ?? '',
            optional($req->requested_at)->format('Y-m-d H:i:s'),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastRow = $this->rowCount;
                $lastCol = 'E';
                $this->applyDefaultSheetStyles($event, $lastRow, $lastCol);
                $sheet = $event->sheet->getDelegate();
                $sheet->insertNewRowBefore(1, 1);
                $sheet->setCellValue('A1', 'Destruction Requests');
                $sheet->mergeCells('A1:' . $lastCol . '1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getRowDimension(2)->setRowHeight(22);
            },
        ];
    }
}


