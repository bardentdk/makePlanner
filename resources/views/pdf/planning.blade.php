<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Planning</title>
    <style>
        @page { margin: 5mm; }
        body { font-family: Arial, sans-serif; font-size: 9px; text-transform: uppercase; }
        .header { text-align: center; margin-bottom: 10px; border: 1px solid #000; padding: 5px; background-color: #f9f9f9; }
        h1 { margin: 0; font-size: 14px; }
        h2 { margin: 3px 0 0; font-size: 11px; font-weight: normal; font-style: italic; }

        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { padding: 2px; text-align: center; height: 14px; vertical-align: middle; border-bottom: 1px solid #ccc; border-right: 1px solid #ccc; }
        
        /* Bordures épaisses pour séparer les mois (chaque 3 colonnes) */
        td:nth-child(3n), th:nth-child(3n) { border-right: 2px solid #000 !important; }
        td:first-child, th:first-child { border-left: 2px solid #000 !important; }

        .month-header { background-color: #A6A6A6; color: white; font-weight: bold; border: 2px solid #000; }
        .sub-header { border-bottom: 2px solid #000 !important; font-weight: bold; font-size: 8px; }
        .total-header { background-color: #4B5563; color: white; font-weight: bold; border: 1px solid #000; width: 40px;}

        .weekend { background-color: #F2F2F2 !important; color: #444; } 
        .empty-day { background-color: #808080 !important; }
        
        /* Cellule de contenu (C) */
        .content-cell { font-weight: bold; font-size: 9px; color: #000; }

        /* Styles Pied de page */
        .footer-label { text-align: right; padding-right: 4px; font-size: 7px; font-weight: bold; border-right: none!important; }
        .footer-val { font-weight: bold; font-size: 9px; border-left: 1px solid #ccc; border: 1px solid #000; }
        .total-box { border: 2px solid #000 !important; font-weight: bold; font-size: 11px; }

        /* Couleurs */
        .bg-centre { background-color: #DBEAFE; } /* Bleu */
        .bg-recherche { background-color: #E2D0F9; } /* Mauve */
        .bg-revision { background-color: #BBF7D0; } /* Vert */
        .bg-stage { background-color: #FCE4D6; } /* Rose */
    </style>
</head>
<body>
    
    @php
        $allDays = collect($grid)->pluck('days')->flatten();

        // Total Global : C + RS uniquement (Plus de R)
        $totalGlobalCentre = $allDays->reduce(function($carry, $day) {
            if (empty($day->content)) return $carry;
            $h = $day->hours ?? 0;
            $code = $day->raw_code ?? $day->content;

            // On retire 'R' ici aussi
            if (in_array($code, ['C', 'RS'])) {
                return $carry + $h;
            }
            return $carry;
        }, 0);

        // Total Stage = S uniquement
        $totalGlobalStage = $allDays->where('raw_code', 'S')->sum('hours');
    @endphp

    <div class="header">
        <h1>{{ $planning->title }}</h1>
        <h2>Du {{ $planning->start_date->format('d/m/Y') }} au {{ $planning->end_date->format('d/m/Y') }}</h2>
    </div>

    <table>
        <thead>
            <tr>
                @foreach($grid as $monthData)
                    <th colspan="3" class="month-header">{{ $monthData['month_label'] }}</th>
                @endforeach
                <th class="total-header">TOTAL</th>
            </tr>
            <tr>
                @foreach($grid as $monthData)
                    <th class="sub-header" style="width:15px;">J</th>
                    <th class="sub-header" style="width:15px;"></th>
                    <th class="sub-header" style="width:25px;">C</th>
                @endforeach
                <th class="sub-header">GLO.</th>
            </tr>
        </thead>
        <tbody>
            {{-- BOUCLE DES JOURS (1 à 31) --}}
            @for($day = 1; $day <= 31; $day++)
                <tr>
                    @foreach($grid as $monthData)
                        @php $dayDTO = collect($monthData['days'])->first(fn($d) => $d->date->day === $day); @endphp
                        
                        @if($dayDTO)
                            @php $isWeekend = $dayDTO->type === 'weekend'; @endphp
                            
                            <td class="{{ $isWeekend ? 'weekend' : '' }}">{{ $day }}</td>
                            <td class="{{ $isWeekend ? 'weekend' : '' }}">{{ $dayDTO->dayLetter }}</td>
                            
                            {{-- Cellule Contenu avec Couleur --}}
                            <td class="content-cell" 
                                style="background-color: {{ $dayDTO->color }}; @if($isWeekend) background-color:#F2F2F2; @endif">
                                {{-- On affiche le contenu (7, S, F...) --}}
                                {{ $dayDTO->content }}
                            </td>
                        @else
                            {{-- Jours inexistants (ex: 30 fév) --}}
                            <td class="empty-day"></td><td class="empty-day"></td><td class="empty-day"></td>
                        @endif
                    @endforeach
                    
                    {{-- Colonne Totale vide sur les lignes jours --}}
                    <td style="background-color:#eee; border-bottom:1px solid #fff;"></td>
                </tr>
            @endfor

            {{-- SÉPARATEUR AVANT LE PIED DE PAGE --}}
            <tr>
                <td colspan="{{ count($grid) * 3 + 1 }}" style="height:5px; border:none; border-top:2px solid #000;"></td>
            </tr>

            {{-- 1. LIGNE : H. CENTRE (Total Bleu) --}}
            <tr>
                @foreach($grid as $monthData)
                    <td colspan="2" class="footer-label">H. CENTRE</td>
                    <td class="footer-val bg-centre">
                        @php
                            // MODIFICATION ICI : On ne compte que C (Cours) et RS (Recherche)
                            // On a retiré 'R' de ce tableau
                            $sum = collect($monthData['days'])->reduce(function($c, $d) {
                                $code = $d->raw_code ?? $d->content;
                                return (in_array($code, ['C', 'RS']) || (is_numeric($d->content) && $code !== 'R')) 
                                       ? $c + ($d->hours ?? 0) 
                                       : $c;
                            }, 0);
                        @endphp
                        {{ $sum > 0 ? $sum : '' }}
                    </td>
                @endforeach
                {{-- Total Global Centre --}}
                <td class="total-box bg-centre">{{ $totalGlobalCentre }}</td>
            </tr>

            {{-- LIGNE : RÉVISIONS (Vert) --}}
            <tr>
                @foreach($grid as $monthData)
                    <td colspan="2" class="footer-label">RÉVISIONS</td>
                    <td class="footer-val bg-revision">
                        @php
                            // On filtre sur le code interne 'R' généré par le service
                            $sum = collect($monthData['days'])
                                ->where('raw_code', 'R')
                                ->sum('hours');
                        @endphp
                        {{ $sum > 0 ? $sum : '' }}
                    </td>
                @endforeach
                {{-- Pas de total global nécessaire ici selon votre demande --}}
                <td style="background-color:#eee;">-</td>
            </tr>

            {{-- 3. LIGNE : RECHERCHE (Mauve - Mensuel uniquement) --}}
            <tr>
                @foreach($grid as $monthData)
                    <td colspan="2" class="footer-label">RECHERCHE</td>
                    <td class="footer-val bg-recherche">
                        @php
                            // On filtre STRICTEMENT sur le code 'RS'
                            $sum = collect($monthData['days'])
                                ->where('raw_code', 'RS')
                                ->sum('hours');
                        @endphp
                        {{-- Affichage du mensuel --}}
                        {{ $sum > 0 ? $sum : '' }}
                    </td>
                @endforeach
                {{-- Pas de total global demandé --}}
                <td style="background-color:#eee;">-</td>
            </tr>

            {{-- 4. LIGNE : STAGE (Rose) --}}
            <tr>
                @foreach($grid as $monthData)
                    <td colspan="2" class="footer-label">STAGE</td>
                    <td class="footer-val bg-stage">
                        @php
                            $sum = collect($monthData['days'])
                                ->where('raw_code', 'S')
                                ->sum('hours');
                        @endphp
                        {{ $sum > 0 ? $sum : '' }}
                    </td>
                @endforeach
                {{-- Total Global Stage --}}
                <td class="total-box bg-stage">{{ $totalGlobalStage }}</td>
            </tr>

        </tbody>
    </table>
</body>
</html>