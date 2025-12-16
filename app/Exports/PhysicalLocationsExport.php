<?php

namespace App\Exports;

use App\Exports\Concerns\DefaultStyles;
use App\Models\Box;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;

class PhysicalLocationsExport implements FromQuery, WithHeadings, WithMapping, WithEvents, WithCharts
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
            'Code de boîte',
            'Salle',
            'Rangée',
            'Étagère',
            'Description',
            'Nombre de documents',
            'Date de création de la boîte',
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

    protected function addDonutChart(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $name, string $title, string $labelsRange, string $valuesRange, string $topLeft, string $bottomRight): void
    {
        $dataseriesLabels = [
            new DataSeriesValues('String', $valuesRange, null, 1),
        ];

        $xAxisTickValues = [
            new DataSeriesValues('String', $labelsRange, null, null),
        ];

        $dataSeriesValues = [
            new DataSeriesValues('Number', $valuesRange, null, null),
        ];

        $series = new DataSeries(
            DataSeries::TYPE_DONUTCHART,
            null,
            range(0, count($dataSeriesValues) - 1),
            $dataseriesLabels,
            $xAxisTickValues,
            $dataSeriesValues
        );

        $layout = new Layout();
        $layout->setShowPercent(true);

        $plotArea = new PlotArea($layout, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $chartTitle = new Title($title);

        $chart = new Chart($name, $chartTitle, $legend, $plotArea);
        $chart->setTopLeftPosition($topLeft);
        $chart->setBottomRightPosition($bottomRight);

        $sheet->addChart($chart);
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

                $sheet->setCellValue('A1', 'Rapport sur les emplacements physiques');
                $sheet->mergeCells('A1:G1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

                $sheet->setCellValue('A2', 'Généré le : ' . now()->format('d/m/Y H:i'));
                $sheet->setCellValue('A3', 'Filtres actifs : Aucun (toutes les boîtes)');
                $total = $this->totalBoxes ?? ($this->rowCount - 1);
                $sheet->setCellValue('A4', 'Total des boîtes : ' . $total);

                // Stats tables
                $sheet->setCellValue('I1', 'Boîtes par salle');
                $sheet->setCellValue('I2', 'Salle');
                $sheet->setCellValue('J2', 'Total');
                $rowRoom = 3;
                foreach ($this->boxesByRoom as $room => $count) {
                    $sheet->setCellValue("I{$rowRoom}", $room ?: 'N/A');
                    $sheet->setCellValue("J{$rowRoom}", $count);
                    $rowRoom++;
                }

                $sheet->setCellValue('L1', 'Documents par emplacement');
                $sheet->setCellValue('L2', 'Emplacement');
                $sheet->setCellValue('M2', 'Total');
                $rowBox = 3;
                foreach ($this->documentsByBox as $location => $count) {
                    $sheet->setCellValue("L{$rowBox}", $location ?: 'N/A');
                    $sheet->setCellValue("M{$rowBox}", $count);
                    $rowBox++;
                }

                if ($rowRoom > 3) {
                    $labelsRange = "'{$sheetTitle}'!I3:I" . ($rowRoom - 1);
                    $valuesRange = "'{$sheetTitle}'!J3:J" . ($rowRoom - 1);
                    $this->addDonutChart($sheet, 'boxes_by_room', 'Boîtes par salle', $labelsRange, $valuesRange, 'I6', 'N20');
                }

                if ($rowBox > 3) {
                    $labelsRange = "'{$sheetTitle}'!L3:L" . ($rowBox - 1);
                    $valuesRange = "'{$sheetTitle}'!M3:M" . ($rowBox - 1);
                    $this->addDonutChart($sheet, 'docs_by_location', 'Documents par emplacement', $labelsRange, $valuesRange, 'I21', 'N35');
                }

                // Freeze data header row
                $sheet->freezePane('A6');
            },
        ];
    }

    public function charts(): array
    {
        return [];
    }
}
