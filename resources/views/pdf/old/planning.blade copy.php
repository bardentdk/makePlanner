<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Planning</title>
    <style>
        @page { margin: 5mm; }
        body { font-family: Arial, sans-serif; font-size: 9px; text-transform: uppercase!important}
        .header { text-align: center; margin-bottom: 15px; border: 1px solid #000; padding: 5px; background-color: #f9f9f9; }
        h1 { margin: 0; font-size: 14px; text-transform: uppercase; }
        h2 { margin: 3px 0 0; font-size: 11px; font-weight: normal; font-style: italic; }

        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { padding: 1px; text-align: center; height: 14px; vertical-align: middle; border-bottom: 1px solid #ccc; border-right: 1px solid #ccc; }
        
        td:nth-child(3n), th:nth-child(3n) { border-right: 2px solid #000 !important; }
        td:first-child, th:first-child { border-left: 2px solid #000 !important; }

        .month-header { background-color: #A6A6A6; color: white; font-weight: bold; border-top: 2px solid #000; border-bottom: 2px solid #000; }
        .sub-header { border-bottom: 2px solid #000 !important; font-weight: bold; font-size: 8px; }
        .total-header { background-color: #4B5563; color: white; font-weight: bold; border: 1px solid #000; width: 40px;}

        .weekend { background-color: #F2F2F2 !important; color: #444; } 
        .empty-day { background-color: #808080 !important; }
        .content-cell { font-weight: bold; font-size: 8px; }

        .col-day { width: 15px; }
        .col-letter { width: 15px; }
        .col-content { width: 25px; }
        .col-total { border: 2px solid #000 !important; font-weight: bold; font-size: 10px; }

        .total-row td { border-top: 2px solid #000 !important; }
        .label-cell { 
            text-align: right; 
            padding-right: 5px; 
            font-weight: bold; 
            border-right: 1px solid #fff !important; 
            border-bottom: none !important; 
            border-left: none !important; 
        }
    </style>
</head>
<body>
    
    @php
        $allDays = collect($grid)->pluck('days')->flatten();

        // CORRECTION : Calcul robuste qui fonctionne en mode dynamique
        // On additionne les jours qui contiennent un chiffre (ex: 7, 4) OU les codes RS/R
        
        $totalGlobalCentre = $allDays->reduce(function($carry, $day) {
            // Si la cellule est vide ou c'est un weekend, on ignore
            if (empty($day->content)) return $carry;

            // 1. Si le contenu est un chiffre (ex: 7 ou 4), c'est de la Formation Centre
            if (is_numeric($day->content)) {
                return $carry + (float)$day->content;
            }

            // 2. Si c'est du texte (RS, R), on ajoute les heures réelles (ou 7 par défaut)
            // Note: On exclut 'S' (Stage), 'F' (Férié), 'FC' (Fermeture) du total Centre
            if (in_array($day->content, ['RS', 'R', 'C'])) {
                return $carry + ($day->hours_real ?? 7);
            }

            return $carry;
        }, 0);

        // Calcul du Stage (S)
        $totalGlobalStage = $allDays->where('content', 'S')->sum(function($day) {
            // On prend les heures réelles (pour gérer les demi-journées de stage) ou 7 par défaut
            return $day->hours_real ?? 7;
        });
    @endphp

    <div class="header">
        <h1>{{ $planning->title }}</h1>
        <h2>Période : {{ $planning->start_date->format('d/m/Y') }} au {{ $planning->end_date->format('d/m/Y') }}</h2>
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
                    <th class="sub-header col-day">J</th>
                    <th class="sub-header col-letter"></th>
                    <th class="sub-header col-content">C</th>
                @endforeach
                <th class="sub-header">GLO.</th>
            </tr>
        </thead>
        <tbody>
            @for($day = 1; $day <= 31; $day++)
                <tr>
                    @foreach($grid as $monthData)
                        @php $dayDTO = collect($monthData['days'])->first(fn($d) => $d->date->day === $day); @endphp
                        @if($dayDTO)
                            @php $isWeekend = $dayDTO->type === 'weekend'; @endphp
                            <td class="{{ $isWeekend ? 'weekend' : '' }}">{{ $day }}</td>
                            <td class="{{ $isWeekend ? 'weekend' : '' }}">{{ $dayDTO->dayLetter }}</td>
                            <td class="content-cell {{ $isWeekend ? 'weekend' : '' }}"
                                style="@if(!$isWeekend && $dayDTO->color !== '#FFFFFF') background-color: {{ $dayDTO->color }}; @endif">
                                {{ $dayDTO->content }}
                            </td>
                        @else
                            <td class="empty-day"></td><td class="empty-day"></td><td class="empty-day"></td>
                        @endif
                    @endforeach
                    <td style="background-color:#eee;"></td>
                </tr>
            @endfor
            
            <tr><td colspan="{{ count($grid) * 3 + 1 }}" style="height:5px; border:none;"></td></tr>

            <tr class="total-row">
                @foreach($grid as $monthData)
                    <td colspan="2" class="label-cell" style="font-size:7px;">H. Centre</td>
                    <td style="font-weight:bold; border: 1px solid #000; background-color: #DBEAFE;">
                        @php 
                            $val = collect($monthData['days'])->where('type', 'standard')->sum('content');
                            echo $val > 0 ? $val : '';
                        @endphp
                    </td>
                @endforeach
                <td class="col-total" style="background-color: #DBEAFE;">{{ $totalGlobalCentre }}</td>
            </tr>

            @foreach($phases->unique('code')->sortBy('priority') as $phase)
                @if(!$phase->code) @continue @endif
                <tr>
                    @foreach($grid as $monthData)
                        <td colspan="2" class="label-cell">{{ substr($phase->name, 0, 10) }}</td>
                        <td style="font-weight:bold; border: 1px solid #000; background-color: {{ $phase->color }};">
                            @php
                                $cnt = collect($monthData['days'])->filter(fn($d) => $d->content === $phase->code)->count();
                                $tot = $cnt * $phase->hours_per_day;
                            @endphp
                            {{ $tot > 0 ? $tot : '' }}
                        </td>
                    @endforeach

                    @if($phase->code === 'S')
                        <td class="col-total" style="background-color: {{ $phase->color }};">{{ $totalGlobalStage }}</td>
                    @elseif(in_array($phase->code, ['RS', 'R']))
                        <td class="col-total" style="color:#aaa; font-style:italic;">--</td>
                    @else
                        <td class="col-total" style="background-color: {{ $phase->color }};">
                            {{ $allDays->where('content', $phase->code)->count() * $phase->hours_per_day }}
                        </td>
                    @endif
                </tr>
            @endforeach
            
             <tr>
                @foreach($grid as $monthData)
                     <td colspan="3" style="border:none;"></td>
                @endforeach
                <td class="col-total" style="border-top: 2px solid #000;">
                    {{ $totalGlobalCentre + $totalGlobalStage }}
                </td>
            </tr>

        </tbody>
    </table>
</body>
</html>