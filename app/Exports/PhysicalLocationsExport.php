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
            ->with([
                'shelf.row.room',
                'service.subDepartment.department',
                'documents.category'
            ])
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
            __('Pôle'),
            __('Département'),
            __('Service'),
            __('Catégorie'),
        ];
    }

    public function map($box): array
    {
        $this->rowCount++;

        // Ensure relationships are loaded (though baseQuery should handle it)
        $box->loadMissing([
            'shelf.row.room',
            'service.subDepartment.department',
            'documents.category'
        ]);

        $roomName  = optional(optional(optional($box->shelf)->row)->room)->name ?? '';
        $rowName   = optional(optional($box->shelf)->row)->name ?? '';
        $shelfName = optional($box->shelf)->name ?? '';

        $poleName = optional(optional(optional($box->service)->subDepartment)->department)->name ?? '';
        $deptName = optional(optional($box->service)->subDepartment)->name ?? '';
        $serviceName = optional($box->service)->name ?? '';

        // Get unique categories from documents in this box
        $categories = $box->documents
            ->pluck('category.name')
            ->filter()
            ->unique()
            ->implode(', ');

        return [
            $box->name,
            $roomName,
            $rowName,
            $shelfName,
            $box->description,
            $box->documents_count,
            optional($box->created_at)?->format('d/m/Y H:i'),
            $poleName,
            $deptName,
            $serviceName,
            $categories,
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
                // Extended to K since we added 4 columns
                $lastCol = 'K'; 
                $this->applyDefaultSheetStyles($event, $lastRow, $lastCol);
                $sheet = $event->sheet->getDelegate();
                $sheetTitle = $sheet->getTitle();

                // Insert header rows above table
                $sheet->insertNewRowBefore(1, 5);

                $sheet->setCellValue('A1', __('Physical Locations Report'));
                // Merge across all columns
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

                $sheet->setCellValue('A2', __('Generated on:') . ' ' . now()->format('d/m/Y H:i'));
                $sheet->setCellValue('A3', __('Active filters:') . ' ' . __('None (all boxes)'));
                $total = $this->totalBoxes ?? ($this->rowCount - 1);
                $sheet->setCellValue('A4', __('Total boxes:') . ' ' . $total);

                // Stats tables - Shifted to right significantly, maybe M and P
                // Old was I and L. We added 4 columns. So I+4=M, L+4=P
                $sheet->setCellValue('M1', __('Boxes by room'));
                $sheet->setCellValue('M2', __('Room'));
                $sheet->setCellValue('N2', __('Total'));
                $rowRoom = 3;
                foreach ($this->boxesByRoom as $room => $count) {
                    $sheet->setCellValue("M{$rowRoom}", $room ?: 'N/A');
                    $sheet->setCellValue("N{$rowRoom}", $count);
                    $rowRoom++;
                }

                $sheet->setCellValue('P1', __('Documents by location'));
                $sheet->setCellValue('P2', __('Location'));
                $sheet->setCellValue('Q2', __('Total'));
                $rowBox = 3;
                foreach ($this->documentsByBox as $location => $count) {
                    $sheet->setCellValue("P{$rowBox}", $location ?: 'N/A');
                    $sheet->setCellValue("Q{$rowBox}", $count);
                    $rowBox++;
                }

                // Freeze data header row
                $sheet->freezePane('A6');
            },
        ];
    }


}
