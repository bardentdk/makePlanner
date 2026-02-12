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
                
                // --- 1. CONFIGURATION GLOBALE ---
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(9);
                $sheet->setShowGridlines(false);
                $sheet->getDefaultRowDimension()->setRowHeight(15);
                $sheet->getColumnDimension('A')->setWidth(25); // Colonne des jours

                // --- 2. CALCULS GLOBAUX ---
                $allDays = collect($this->grid)->pluck('days')->flatten();
                
                // MODIFICATION : On retire 'R'
                $totalGlobalCentre = $allDays->filter(function($d) {
                    $code = $d->raw_code ?? '';
                    return in_array($code, ['C', 'RS']); // R retiré
                })->sum('hours');

                // Total Stage = S
                $totalGlobalStage = $allDays->where('raw_code', 'S')->sum('hours');

                // --- 3. EN-TÊTES ---
                $nbMonths = count($this->grid);
                // 3 colonnes par mois (J, Lettre, Contenu)
                $lastColIndex = 1 + ($nbMonths * 3); 
                
                // Colonne TOTAL à droite
                $totalColIndex = $lastColIndex + 1;
                $totalColLetter = Coordinate::stringFromColumnIndex($totalColIndex);
                
                // Titre
                $sheet->mergeCells("A1:{$totalColLetter}1");
                $sheet->setCellValue('A1', mb_strtoupper($this->planning->title));
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Période
                $sheet->mergeCells("A2:{$totalColLetter}2");
                $sheet->setCellValue('A2', sprintf("Période : %s au %s", $this->planning->start_date->format('d/m/Y'), $this->planning->end_date->format('d/m/Y')));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'italic' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Case "TOTAL GLOBAL"
                $sheet->setCellValue("{$totalColLetter}4", "TOTAL");
                $sheet->getStyle("{$totalColLetter}4")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4B5563']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ]);

                // --- 4. GÉNÉRATION DE LA GRILLE ---
                $currentColIndex = 2;
                $rowStart = 6;
                $rowEnd = $rowStart + 30; // 31 jours max

                foreach ($this->grid as $monthKey => $monthData) {
                    $colDay = Coordinate::stringFromColumnIndex($currentColIndex);
                    $colLetter = Coordinate::stringFromColumnIndex($currentColIndex + 1);
                    $colContent = Coordinate::stringFromColumnIndex($currentColIndex + 2); // La colonne C

                    $sheet->getColumnDimension($colDay)->setWidth(4);
                    $sheet->getColumnDimension($colLetter)->setWidth(4);
                    $sheet->getColumnDimension($colContent)->setWidth(8);

                    // Header Mois
                    $sheet->mergeCells("{$colDay}4:{$colContent}4");
                    $sheet->setCellValue("{$colDay}4", $monthData['month_label']);
                    $sheet->getStyle("{$colDay}4")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFA6A6A6']], 
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                    ]);

                    // Sous-titres (J, Let, C)
                    $sheet->setCellValue("{$colDay}5", "J");
                    $sheet->setCellValue("{$colContent}5", "C");
                    $sheet->getStyle("{$colDay}5:{$colContent}5")->applyFromArray(['font' => ['bold' => true, 'size' => 8], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER], 'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM]]]);

                    // BOUCLE JOURS (1-31)
                    for ($day = 1; $day <= 31; $day++) {
                        $row = $rowStart + ($day - 1);
                        
                        // Style par défaut
                        $sheet->getStyle("{$colDay}{$row}:{$colContent}{$row}")->applyFromArray(['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']]]]);

                        $dayDTO = collect($monthData['days'])->first(fn($d) => $d->date->day === $day);

                        if ($dayDTO) {
                            $sheet->setCellValue("{$colDay}{$row}", $day);
                            $sheet->setCellValue("{$colLetter}{$row}", $dayDTO->dayLetter);
                            
                            // Contenu : "7", "RS", "S", "F"...
                            $sheet->setCellValue("{$colContent}{$row}", $dayDTO->content);

                            // Couleurs
                            if ($dayDTO->type === 'weekend') {
                                $sheet->getStyle("{$colDay}{$row}:{$colContent}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF2F2F2');
                            }
                            else if ($dayDTO->color && $dayDTO->color !== '#FFFFFF') {
                                $sheet->getStyle("{$colContent}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($this->getArgb($dayDTO->color));
                            }
                        } else {
                            // Jours inexistants (ex: 30 fév)
                            $sheet->getStyle("{$colDay}{$row}:{$colContent}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF808080');
                        }
                    }

                    // --- 5. CALCULS MENSUELS DU PIED DE PAGE ---
                    $rowFooter = $rowEnd + 2; // Séparation
                    $daysCol = collect($monthData['days']);

                    // A. Ligne H. CENTRE (Bleu) -> Somme C + R + RS
                    $sumCentre = $daysCol->filter(fn($d) => in_array($d->raw_code ?? '', ['C', 'RS']))->sum('hours');
                    $sheet->setCellValue("{$colContent}{$rowFooter}", $sumCentre > 0 ? $sumCentre : '');
                    $sheet->getStyle("{$colContent}{$rowFooter}")->applyFromArray(['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDBEAFE']], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
                    
                    // Label à gauche (seulement pour la première colonne, mais ici on le met pas pour garder la structure grille)
                    // On gère les labels globaux plus bas.

                    // B. Ligne RÉVISIONS (Vert) -> Somme R
                    $rowFooter++;
                    $sumRev = $daysCol->where('raw_code', 'R')->sum('hours');
                    $sheet->setCellValue("{$colContent}{$rowFooter}", $sumRev > 0 ? $sumRev : '');
                    $sheet->getStyle("{$colContent}{$rowFooter}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFBBF7D0']], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);

                    // C. Ligne RECHERCHE (Mauve) -> Somme RS
                    $rowFooter++;
                    $sumRS = $daysCol->where('raw_code', 'RS')->sum('hours');
                    $sheet->setCellValue("{$colContent}{$rowFooter}", $sumRS > 0 ? $sumRS : '');
                    $sheet->getStyle("{$colContent}{$rowFooter}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2D0F9']], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);

                    // D. Ligne STAGE (Rose) -> Somme S
                    $rowFooter++;
                    $sumStage = $daysCol->where('raw_code', 'S')->sum('hours');
                    $sheet->setCellValue("{$colContent}{$rowFooter}", $sumStage > 0 ? $sumStage : '');
                    $sheet->getStyle("{$colContent}{$rowFooter}")->applyFromArray(['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFCE4D6']], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);

                    $currentColIndex += 3;
                }

                // --- 6. LABELS ET TOTAUX GLOBAUX (Colonne A et Dernière Colonne) ---
                $rowFooterStart = $rowEnd + 2;

                // Labels Colonne A
                $sheet->setCellValue("A{$rowFooterStart}", "H. CENTRE");
                $sheet->getStyle("A{$rowFooterStart}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                $sheet->setCellValue("A".($rowFooterStart+1), "RÉVISIONS");
                $sheet->getStyle("A".($rowFooterStart+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                $sheet->setCellValue("A".($rowFooterStart+2), "RECHERCHE");
                $sheet->getStyle("A".($rowFooterStart+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->setCellValue("A".($rowFooterStart+3), "STAGE");
                $sheet->getStyle("A".($rowFooterStart+3))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Totaux Globaux (Dernière Colonne)
                // H. Centre
                $sheet->setCellValue("{$totalColLetter}{$rowFooterStart}", $totalGlobalCentre);
                $sheet->getStyle("{$totalColLetter}{$rowFooterStart}")->applyFromArray(['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDBEAFE']], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);

                // Révisions (Pas de total global demandé, on met -)
                $sheet->setCellValue("{$totalColLetter}".($rowFooterStart+1), "-");
                $sheet->getStyle("{$totalColLetter}".($rowFooterStart+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Recherche (Pas de total global demandé, on met -)
                $sheet->setCellValue("{$totalColLetter}".($rowFooterStart+2), "-");
                $sheet->getStyle("{$totalColLetter}".($rowFooterStart+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Stage
                $sheet->setCellValue("{$totalColLetter}".($rowFooterStart+3), $totalGlobalStage);
                $sheet->getStyle("{$totalColLetter}".($rowFooterStart+3))->applyFromArray(['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFCE4D6']], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);

            }
        ];
    }
}