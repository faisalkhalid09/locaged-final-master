<?php

namespace App\Exports;

use App\Exports\Concerns\DefaultStyles;
use App\Models\Box;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class PhysicalLocationsExport implements FromQuery, WithHeadings, WithMapping, WithEvents
{
    use DefaultStyles;

    protected int $rowCount = 1;

    /** @var array<string,int>|null */
    protected ?array $boxesByRoom = null;

    /** @var array<string,int>|null */
    protected ?array $documentsByBox = null;

    protected ?int $totalBoxes = null;

    protected function baseQuery(): Builder
    {
        // Export all boxes in the physical locations hierarchy, with the
        // number of linked documents (which may be zero). Document global
        // scopes on the Document model still enforce org visibility
        // when counting documents.
        return Box::query()
            ->with(['shelf.row.room'])
            ->withCount('documents');
    }

    public function query(): Builder
    {
        return $this->baseQuery()->orderBy('id');
    }

    public function headings(): array
    {
        return [
            __('Box Code'),
            __('Room'),
            __('Row'),
            __('Shelf'),
            __('Description'),
            __('Number of documents'),
            __('Box creation date'),
        ];
    }

    public function map($box): array
    {
        $this->rowCount++;

        // Ensure relationships are loaded
        $box->loadMissing('shelf.row.room');

        $roomName  = optional(optional(optional($box->shelf)->row)->room)->name ?? '';
        $rowName   = optional(optional($box->shelf)->row)->name ?? '';
        $shelfName = optional($box->shelf)->name ?? '';

        return [
            $box->name,
            $roomName,
            $rowName,
            $shelfName,
            $box->description,
            $box->documents_count,
            optional($box->created_at)?->format('d/m/Y H:i'),
        ];
    }

    protected function ensureStatsLoaded(): void
    {
        if ($this->boxesByRoom !== null) {
            return;
        }

        $boxes = $this->baseQuery()->get();
        $this->totalBoxes = $boxes->count();

        $this->boxesByRoom = $boxes->groupBy(function ($box) {
            return optional(optional(optional($box->shelf)->row)->room)->name ?? 'Sans salle';
        })->map->count()->toArray();

        $this->documentsByBox = $boxes->mapWithKeys(function ($box) {
            $label = (string) $box;
            return [$label => (int) $box->documents_count];
        })->toArray();
    }



    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->ensureStatsLoaded();

                $lastRow = $this->rowCount;
                $lastCol = 'G';
                $this->applyDefaultSheetStyles($event, $lastRow, $lastCol);
                $sheet = $event->sheet->getDelegate();
                $sheetTitle = $sheet->getTitle();

                // Insert header rows above table
                $sheet->insertNewRowBefore(1, 5);

                $sheet->setCellValue('A1', __('Physical Locations Report'));
                $sheet->mergeCells('A1:G1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

                $sheet->setCellValue('A2', __('Generated on:') . ' ' . now()->format('d/m/Y H:i'));
                $sheet->setCellValue('A3', __('Active filters:') . ' ' . __('None (all boxes)'));
                $total = $this->totalBoxes ?? ($this->rowCount - 1);
                $sheet->setCellValue('A4', __('Total boxes:') . ' ' . $total);

                // Stats tables
                $sheet->setCellValue('I1', __('Boxes by room'));
                $sheet->setCellValue('I2', __('Room'));
                $sheet->setCellValue('J2', __('Total'));
                $rowRoom = 3;
                foreach ($this->boxesByRoom as $room => $count) {
                    $sheet->setCellValue("I{$rowRoom}", $room ?: 'N/A');
                    $sheet->setCellValue("J{$rowRoom}", $count);
                    $rowRoom++;
                }

                $sheet->setCellValue('L1', __('Documents by location'));
                $sheet->setCellValue('L2', __('Location'));
                $sheet->setCellValue('M2', __('Total'));
                $rowBox = 3;
                foreach ($this->documentsByBox as $location => $count) {
                    $sheet->setCellValue("L{$rowBox}", $location ?: 'N/A');
                    $sheet->setCellValue("M{$rowBox}", $count);
                    $rowBox++;
                }

                // Freeze data header row
                $sheet->freezePane('A6');
            },
        ];
    }


}
