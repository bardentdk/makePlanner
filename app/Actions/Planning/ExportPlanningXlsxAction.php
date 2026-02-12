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
        if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        return 'FF' . strtoupper($hex);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Style global
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(9);
                $sheet->setShowGridlines(false);
                $sheet->getDefaultRowDimension()->setRowHeight(15);
                $sheet->getColumnDimension('A')->setWidth(30);

                // --- CALCULS GLOBAUX RÉELS ---
                $allDays = collect($this->grid)->pluck('days')->flatten();
                
                // Le TOTAL CENTRE englobe : Standard + RS + R
                $totalGlobalCentre = $allDays->where('type', 'standard')->sum('content') 
                                   + ($allDays->whereIn('content', ['RS', 'R'])->count() * 7);
                                   
                // Le TOTAL STAGE englobe : S
                $totalGlobalStage = $allDays->where('content', 'S')->count() * 7;

                // --- STRUCTURE ---
                $nbMonths = count($this->grid);
                $lastColIndex = 1 + ($nbMonths * 3); 
                $lastColLetter = Coordinate::stringFromColumnIndex($lastColIndex);
                
                // Colonne TOTAL à droite
                $totalColIndex = $lastColIndex + 1;
                $totalColLetter = Coordinate::stringFromColumnIndex($totalColIndex);
                $sheet->getColumnDimension($totalColLetter)->setWidth(15);

                // En-têtes
                $sheet->mergeCells("A1:{$totalColLetter}1");
                $sheet->setCellValue('A1', mb_strtoupper($this->planning->title));
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells("A2:{$totalColLetter}2");
                $sheet->setCellValue('A2', sprintf("Période : %s au %s", $this->planning->start_date->format('d/m/Y'), $this->planning->end_date->format('d/m/Y')));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'italic' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->setCellValue("{$totalColLetter}4", "TOTAL GLOBAL");
                $sheet->getStyle("{$totalColLetter}4")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4B5563']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ]);

                // --- GRILLE ---
                $currentColIndex = 2;
                $rowStart = 6;

                foreach ($this->grid as $monthKey => $monthData) {
                    $colDay = Coordinate::stringFromColumnIndex($currentColIndex);
                    $colLetter = Coordinate::stringFromColumnIndex($currentColIndex + 1);
                    $colContent = Coordinate::stringFromColumnIndex($currentColIndex + 2);

                    $sheet->getColumnDimension($colDay)->setWidth(5);
                    $sheet->getColumnDimension($colLetter)->setWidth(5);
                    $sheet->getColumnDimension($colContent)->setWidth(7);

                    // Header Mois
                    $sheet->mergeCells("{$colDay}4:{$colContent}4");
                    $dateObj = \Carbon\Carbon::createFromFormat('Y-m', $monthKey)->startOfMonth();
                    $sheet->setCellValue("{$colDay}4", mb_strtoupper($dateObj->translatedFormat('M-y')));
                    $sheet->getStyle("{$colDay}4")->applyFromArray(['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFA6A6A6']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);

                    $sheet->setCellValue("{$colDay}5", "J");
                    $sheet->setCellValue("{$colContent}5", "C");
                    $sheet->getStyle("{$colDay}5:{$colContent}5")->applyFromArray(['font' => ['bold' => true, 'size' => 8], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER], 'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM]]]);

                    // Jours
                    for ($day = 1; $day <= 31; $day++) {
                        $row = $rowStart + ($day - 1);
                        $sheet->getStyle("{$colDay}{$row}:{$colContent}{$row}")->applyFromArray(['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']]]]);

                        $dayDTO = collect($monthData['days'])->first(fn($d) => $d->date->day === $day);

                        if ($dayDTO) {
                            $sheet->setCellValue("{$colDay}{$row}", $day);
                            $sheet->setCellValue("{$colLetter}{$row}", $dayDTO->dayLetter);
                            $sheet->setCellValue("{$colContent}{$row}", $dayDTO->content);

                            if ($dayDTO->type === 'weekend') {
                                $sheet->getStyle("{$colDay}{$row}:{$colContent}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF2F2F2');
                            }
                            else if ($dayDTO->color && $dayDTO->color !== '#FFFFFF') {
                                $sheet->getStyle("{$colContent}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($this->getArgb($dayDTO->color));
                            }
                        } else {
                            $sheet->getStyle("{$colDay}{$row}:{$colContent}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF808080');
                        }
                    }

                    // --- TOTAUX MENSUELS ---
                    $rowTotal = 38;
                    $range = "{$colContent}6:{$colContent}36";
                    
                    // Ligne 1 : Formation Standard (Bleu)
                    $sheet->setCellValue("{$colContent}{$rowTotal}", "=SUM({$range})");
                    $sheet->getStyle("{$colContent}{$rowTotal}")->applyFromArray(['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDBEAFE']]]);
                    $rowTotal++;

                    // Autres lignes (RS, R, S, F...)
                    $globalUniquePhases = $this->planning->phases->unique('code')->sortBy('priority');
                    foreach ($globalUniquePhases as $phase) {
                        if (!$phase->code) continue;
                        $sheet->setCellValue("{$colContent}{$rowTotal}", "=COUNTIF({$range},\"{$phase->code}\")*{$phase->hours_per_day}");
                        $style = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];
                        if ($phase->color) $style['fill'] = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $this->getArgb($phase->color)]];
                        $sheet->getStyle("{$colContent}{$rowTotal}")->applyFromArray($style);
                        $rowTotal++;
                    }
                    $currentColIndex += 3;
                }

                // --- COLONNE TOTAL GLOBAL ---
                $rowTotal = 38;
                
                // Ligne H. CENTRE (Contient Standard + RS + R)
                $sheet->setCellValue("A{$rowTotal}", "H. Centre (Form+RS+R)");
                $sheet->getStyle("A{$rowTotal}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->setCellValue("{$totalColLetter}{$rowTotal}", $totalGlobalCentre);
                $sheet->getStyle("{$totalColLetter}{$rowTotal}")->applyFromArray(['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDBEAFE']], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
                $rowTotal++;

                // Autres Lignes
                foreach ($globalUniquePhases as $phase) {
                    if (!$phase->code) continue;
                    $sheet->setCellValue("A{$rowTotal}", mb_strtoupper($phase->name));
                    $sheet->getStyle("A{$rowTotal}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Si c'est STAGE -> On affiche le total
                    if ($phase->code === 'S') {
                        $value = $totalGlobalStage;
                        $style = ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $this->getArgb($phase->color)]]];
                    } 
                    // Si c'est RS ou R -> On met "--" car déjà dans H. Centre
                    elseif (in_array($phase->code, ['RS', 'R'])) {
                        $value = '--';
                        $style = ['font' => ['italic' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]];
                    } 
                    // Férié ou autre -> On compte
                    else {
                        $value = $allDays->where('content', $phase->code)->count() * $phase->hours_per_day;
                        $style = [];
                        if ($phase->color) $style['fill'] = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $this->getArgb($phase->color)]];
                    }

                    $sheet->setCellValue("{$totalColLetter}{$rowTotal}", $value);
                    $style['borders'] = ['allBorders' => ['borderStyle' => Border::BORDER_THIN]];
                    $sheet->getStyle("{$totalColLetter}{$rowTotal}")->applyFromArray($style);
                    $rowTotal++;
                }

                // TOTAL GENERAL (Centre + Stage)
                $sheet->setCellValue("A{$rowTotal}", "TOTAL GÉNÉRAL");
                $sheet->getStyle("A{$rowTotal}")->getFont()->setBold(true);
                
                $sheet->setCellValue("{$totalColLetter}{$rowTotal}", $totalGlobalCentre + $totalGlobalStage);
                $sheet->getStyle("{$totalColLetter}{$rowTotal}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]]
                ]);
            }
        ];
    }
}