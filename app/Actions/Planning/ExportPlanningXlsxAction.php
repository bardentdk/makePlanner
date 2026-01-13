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
use PhpOffice\PhpSpreadsheet\Style\Color;
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
        // Nom de l'onglet (Max 30 chars pour Excel)
        return substr($this->planning->title, 0, 30);
    }

    /**
     * Convertit proprement n'importe quel hexadécimal en ARGB pour Excel.
     */
    private function getArgb(?string $hex): string
    {
        if (!$hex) return 'FFFFFFFF';
        
        // Nettoyage
        $hex = str_replace('#', '', $hex);

        // Format court (ex: F00 -> FF0000)
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        // Si valide, on ajoute le canal Alpha (FF) devant
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
                
                // --- 0. STYLE PAR DÉFAUT (GLOBAL) ---
                // Police standard Excel
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);
                $sheet->setShowGridlines(false); // Pas de grille par défaut
                $sheet->getDefaultRowDimension()->setRowHeight(15);
                
                // Colonne A (Libellés) : Largeur fixe
                $sheet->getColumnDimension('A')->setWidth(35);

                // Récupération des phases pour les totaux
                $phases = $this->planning->phases->sortBy('priority');

                // --- 1. EN-TÊTE DU DOCUMENT ---
                $nbMonths = count($this->grid);
                $lastColIndex = 1 + ($nbMonths * 3); // A + 3 colonnes par mois
                $lastColLetter = Coordinate::stringFromColumnIndex($lastColIndex);

                // Titre
                $sheet->mergeCells("A1:{$lastColLetter}1");
                $sheet->setCellValue('A1', "PLANNING PRÉVISIONNEL : " . mb_strtoupper($this->planning->title));
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Sous-titre : PÉRIODE (Plus de nom d'apprenant)
                $sheet->mergeCells("A2:{$lastColLetter}2");
                $sheet->setCellValue('A2', sprintf(
                    "Période du %s au %s",
                    $this->planning->start_date->format('d/m/Y'),
                    $this->planning->end_date->format('d/m/Y')
                ));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'italic' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                
                // Espace avant le tableau
                $sheet->getRowDimension(3)->setRowHeight(10);

                // --- 2. GÉNÉRATION DE LA GRILLE ---
                $currentColIndex = 2; // Commence colonne B
                $rowStart = 6;        // Les jours commencent ligne 6
                
                foreach ($this->grid as $monthKey => $monthData) {
                    $colDay     = Coordinate::stringFromColumnIndex($currentColIndex);
                    $colLetter  = Coordinate::stringFromColumnIndex($currentColIndex + 1);
                    $colContent = Coordinate::stringFromColumnIndex($currentColIndex + 2);

                    // Largeurs EXACTES pour l'effet miroir
                    $sheet->getColumnDimension($colDay)->setWidth(4);     // J
                    $sheet->getColumnDimension($colLetter)->setWidth(4);  // Lettre
                    $sheet->getColumnDimension($colContent)->setWidth(6); // Contenu

                    // -- HEADER MOIS (Ligne 4) --
                    $sheet->mergeCells("{$colDay}4:{$colContent}4");
                    $dateObj = \Carbon\Carbon::createFromFormat('Y-m', $monthKey)->startOfMonth();
                    $sheet->setCellValue("{$colDay}4", Date::dateTimeToExcel($dateObj));
                    
                    $sheet->getStyle("{$colDay}4")->applyFromArray([
                        'numberFormat' => ['formatCode' => 'mmm-yy'], // ex: janv-25
                        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4472C4']], // Bleu standard
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN]]
                    ]);

                    // -- SUB-HEADER (Ligne 5) --
                    $sheet->setCellValue("{$colDay}5", "J");
                    $sheet->setCellValue("{$colLetter}5", "");
                    $sheet->setCellValue("{$colContent}5", "C");
                    
                    $sheet->getStyle("{$colDay}5:{$colContent}5")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 9],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                    ]);

                    // -- JOURS (Lignes 6 à 36) --
                    for ($day = 1; $day <= 31; $day++) {
                        $currentRow = $rowStart + ($day - 1);
                        
                        // Style de base (Bordures fines partout)
                        $sheet->getStyle("{$colDay}{$currentRow}:{$colContent}{$currentRow}")->applyFromArray([
                            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                        ]);

                        $dayDTO = collect($monthData['days'])->first(fn($d) => $d->date->day === $day);

                        if ($dayDTO) {
                            $sheet->setCellValue("{$colDay}{$currentRow}", $day);
                            $sheet->setCellValue("{$colLetter}{$currentRow}", $dayDTO->dayLetter);
                            $sheet->setCellValue("{$colContent}{$currentRow}", $dayDTO->content);

                            // LOGIQUE DE COLORATION PRIORITAIRE

                            // 1. Week-end : On grise tout (J + Lettre + Contenu)
                            if ($dayDTO->type === 'weekend') {
                                $sheet->getStyle("{$colDay}{$currentRow}:{$colContent}{$currentRow}")
                                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF2F2F2'); // Gris clair
                                
                                $sheet->getStyle("{$colDay}{$currentRow}:{$colContent}{$currentRow}")
                                    ->getFont()->setColor(new Color('FF999999')); // Texte gris
                            }
                            // 2. Jour Férié ou Phase Spéciale : On colore UNIQUEMENT la cellule de contenu "C"
                            else if ($dayDTO->color && $dayDTO->color !== '#FFFFFF') {
                                $argb = $this->getArgb($dayDTO->color);
                                
                                $sheet->getStyle("{$colContent}{$currentRow}")
                                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($argb);
                            }

                        } else {
                            // Jour inexistant (ex: 30 Février) -> Grisé foncé + Hachuré
                            $sheet->getStyle("{$colDay}{$currentRow}:{$colContent}{$currentRow}")
                                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFBFBFBF');
                        }
                    }

                    // -- TOTAUX DU MOIS (Bas de colonne) --
                    $rowTotal = 38;
                    $range = "{$colContent}6:{$colContent}36";

                    // Total Heures (Somme)
                    $sheet->setCellValue("{$colContent}{$rowTotal}", "=SUM({$range})");
                    $sheet->getStyle("{$colContent}{$rowTotal}")->getFont()->setBold(true);
                    $rowTotal++;

                    // Totaux Phases (NB.SI)
                    foreach ($phases as $phase) {
                        if ($phase->code) {
                            // Formule : Nombre de fois le code * heures/jour
                            $sheet->setCellValue("{$colContent}{$rowTotal}", "=COUNTIF({$range},\"{$phase->code}\")*{$phase->hours_per_day}");
                        }
                        
                        // Couleur de fond pour repérer visuellement
                        if ($phase->color) {
                            $argb = $this->getArgb($phase->color);
                            $sheet->getStyle("{$colContent}{$rowTotal}")
                                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($argb);
                        }
                        $rowTotal++;
                    }
                    
                    // Bordures zone totaux
                    $sheet->getStyle("{$colContent}38:{$colContent}".($rowTotal-1))
                        ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                    // Passer au mois suivant
                    $currentColIndex += 3;
                }

                // --- 3. LÉGENDE (COLONNE A) ---
                $rowLabel = 38;
                
                $sheet->setCellValue("A{$rowLabel}", "HEURES FORMATION");
                $sheet->getStyle("A{$rowLabel}")->getFont()->setBold(true);
                $rowLabel++;

                foreach ($phases as $phase) {
                    $sheet->setCellValue("A{$rowLabel}", mb_strtoupper($phase->name));
                    $rowLabel++;
                }
                
                // Alignement à droite pour coller aux chiffres
                $sheet->getStyle("A38:A".($rowLabel-1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        ];
    }
}