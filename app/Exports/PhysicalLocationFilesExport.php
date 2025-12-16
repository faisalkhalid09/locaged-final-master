<?php

namespace App\Exports;

use App\Exports\Concerns\DefaultStyles;
use App\Models\Document;
use App\Models\PhysicalLocation;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class PhysicalLocationFilesExport implements FromQuery, WithHeadings, WithMapping, WithEvents
{
    use DefaultStyles;

    protected PhysicalLocation $location;
    protected int $rowCount = 1;

    public function __construct(PhysicalLocation $location)
    {
        $this->location = $location;
    }

    public function query(): Builder
    {
        return $this->location->documents()->getQuery()->orderBy('id');
    }

    public function headings(): array
    {
        return ['File Name'];
    }

    public function map($doc): array
    {
        $this->rowCount++;
        return [$doc->title];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastRow = $this->rowCount;
                $lastCol = 'A';
                $this->applyDefaultSheetStyles($event, $lastRow, $lastCol);
                $sheet = $event->sheet->getDelegate();
                $sheet->insertNewRowBefore(1, 1);
                $sheet->setCellValue('A1', 'Location #' . $this->location->id . ' Files');
                $sheet->mergeCells('A1:' . $lastCol . '1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getRowDimension(2)->setRowHeight(22);
            },
        ];
    }
}


