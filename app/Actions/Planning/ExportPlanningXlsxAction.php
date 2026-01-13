<?php

namespace App\Actions\Planning;

use App\Models\Planning;
use App\Services\PlanningGeneratorService;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ExportPlanningXlsxAction implements WithTitle, WithEvents
{
    private $grid;

    public function __construct(
        private Planning $planning,
        private PlanningGeneratorService $generator
    ) {
        $this->grid = $this->generator->generateGrid($this->planning);
    }

    public function title(): string
    {
        return substr($this->planning->title, 0, 30);
    }

    private function getArgb(?string $hex): string
    {
        if (!$hex) return 'FFFFFFFF';
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) === 6) {
            return 'FF' . strtoupper($hex);
        }
        return 'FFFFFFFF';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // STYLE GLOBAL
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(9);
                $sheet->setShowGridlines(false);
                $sheet->getDefaultRowDimension()->setRowHeight(15);
                $sheet->getColumnDimension('A')->setWidth(30);

                // On trie par priorité, MAIS on ne garde qu'une seule entrée par Code pour la légende
                // Cela évite d'avoir 15 lignes "Recherche de stage"
                $phases = $this->planning->phases->sortBy('priority');
                $uniquePhases = $phases->unique('code'); 

                $nbMonths = count($this->grid);
                $lastColIndex = 1 + ($nbMonths * 3); 
                $lastColLetter = Coordinate::stringFromColumnIndex($lastColIndex);

                // EN-TÊTE
                $sheet->mergeCells("A1:{$lastColLetter}1");
                $sheet->setCellValue('A1', mb_strtoupper($this->planning->title));
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells("A2:{$lastColLetter}2");
                $sheet->setCellValue('A2', sprintf(
                    "Période : %s au %s",
                    $this->planning->start_date->format('d/m/Y'),
                    $this->planning->end_date->format('d/m/Y')
                ));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'italic' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                
                $sheet->getRowDimension(3)->setRowHeight(8);

                // GRILLE
                $currentColIndex = 2; // B
                $rowStart = 6;
                
                foreach ($this->grid as $monthKey => $monthData) {
                    $colDay     = Coordinate::stringFromColumnIndex($currentColIndex);
                    $colLetter  = Coordinate::stringFromColumnIndex($currentColIndex + 1);
                    $colContent = Coordinate::stringFromColumnIndex($currentColIndex + 2);

                    $sheet->getColumnDimension($colDay)->setWidth(5);
                    $sheet->getColumnDimension($colLetter)->setWidth(5);
                    $sheet->getColumnDimension($colContent)->setWidth(7);

                    // HEADER MOIS
                    $sheet->mergeCells("{$colDay}4:{$colContent}4");
                    $dateObj = \Carbon\Carbon::createFromFormat('Y-m', $monthKey)->startOfMonth();
                    $sheet->setCellValue("{$colDay}4", Date::dateTimeToExcel($dateObj));
                    
                    $sheet->getStyle("{$colDay}4")->applyFromArray([
                        'numberFormat' => ['formatCode' => 'mmm-yy'],
                        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFA6A6A6']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        'borders' => [
                            'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF000000']],
                            'right' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF000000']],
                            'top' => ['borderStyle' => Border::BORDER_THIN],
                            'left' => ['borderStyle' => Border::BORDER_THIN],
                        ]
                    ]);

                    // SUB-HEADER
                    $sheet->setCellValue("{$colDay}5", "J");
                    $sheet->setCellValue("{$colContent}5", "C");
                    $sheet->getStyle("{$colDay}5:{$colContent}5")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 8],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders' => [
                            'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF000000']],
                            'right' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF000000']],
                            'inside' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']],
                        ]
                    ]);

                    // JOURS
                    for ($day = 1; $day <= 31; $day++) {
                        $row = $rowStart + ($day - 1);
                        
                        $sheet->getStyle("{$colDay}{$row}:{$colContent}{$row}")->applyFromArray([
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']],
                                'right' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF000000']],
                            ]
                        ]);

                        $dayDTO = collect($monthData['days'])->first(fn($d) => $d->date->day === $day);

                        if ($dayDTO) {
                            $sheet->setCellValue("{$colDay}{$row}", $day);
                            $sheet->setCellValue("{$colLetter}{$row}", $dayDTO->dayLetter);
                            $sheet->setCellValue("{$colContent}{$row}", $dayDTO->content);

                            if ($dayDTO->type === 'weekend') {
                                $sheet->getStyle("{$colDay}{$row}:{$colContent}{$row}")
                                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');
                            }
                            else if ($dayDTO->color && $dayDTO->color !== '#FFFFFF') {
                                $argb = $this->getArgb($dayDTO->color);
                                $sheet->getStyle("{$colContent}{$row}")
                                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($argb);
                            }
                        } else {
                            $sheet->getStyle("{$colDay}{$row}:{$colContent}{$row}")
                                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF808080');
                        }
                    }

                    // --- TOTAUX (Bas de colonne) ---
                    $rowTotal = 38;
                    $range = "{$colContent}6:{$colContent}36";

                    // Total Heures
                    $sheet->setCellValue("{$colContent}{$rowTotal}", "=SUM({$range})");
                    $sheet->getStyle("{$colContent}{$rowTotal}")->applyFromArray([
                        'font' => ['bold' => true],
                        'borders' => [
                            'top' => ['borderStyle' => Border::BORDER_MEDIUM],
                            'right' => ['borderStyle' => Border::BORDER_MEDIUM],
                            'left' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']],
                            'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']],
                        ]
                    ]);
                    $rowTotal++;

                    // --- BOUCLE SUR LES PHASES UNIQUES (CORRECTION ICI) ---
                    foreach ($uniquePhases as $phase) {
                        if ($phase->code) {
                            // Le COUNTIF compte TOUS les codes "RS" de la colonne, 
                            // donc une seule ligne de légende suffit pour tout calculer.
                            $sheet->setCellValue("{$colContent}{$rowTotal}", "=COUNTIF({$range},\"{$phase->code}\")*{$phase->hours_per_day}");
                        }
                        
                        $styleArray = [
                            'borders' => [
                                'right' => ['borderStyle' => Border::BORDER_MEDIUM],
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']],
                            ]
                        ];
                        
                        if ($phase->color) {
                            $styleArray['fill'] = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $this->getArgb($phase->color)]];
                        }
                        
                        $sheet->getStyle("{$colContent}{$rowTotal}")->applyFromArray($styleArray);
                        $rowTotal++;
                    }

                    $currentColIndex += 3;
                }

                // --- LÉGENDE (Colonne A) ---
                $rowLabel = 38;
                $sheet->setCellValue("A{$rowLabel}", "Total Heures");
                $sheet->getStyle("A{$rowLabel}")->getFont()->setBold(true);
                $rowLabel++;

                // On utilise aussi uniquePhases ici pour n'afficher qu'une fois le nom
                foreach ($uniquePhases as $phase) {
                    $sheet->setCellValue("A{$rowLabel}", mb_strtoupper($phase->name));
                    $rowLabel++;
                }
                $sheet->getStyle("A38:A".($rowLabel-1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        ];
    }
}