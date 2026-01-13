<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Planning</title>
    <style>
        @page {
            margin: 10mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
        }
        
        /* En-tête */
        .header {
            text-align: center;
            margin-bottom: 20px;
            background-color: #f0f0f0;
            padding: 10px;
            border: 1px solid #000;
        }
        h1 { margin: 0; font-size: 16px; text-transform: uppercase; }
        h2 { margin: 5px 0 0; font-size: 12px; font-weight: normal; font-style: italic; }

        /* Tableau Principal */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* Indispensable pour l'alignement */
        }
        
        th, td {
            border: 1px solid #444; /* Bordure fine grise foncée, style Excel */
            padding: 2px;
            text-align: center;
            height: 14px;
            vertical-align: middle;
            font-size: 9px;
        }

        /* Largeurs Colonnes (Identiques Excel) */
        .col-day { width: 18px; }
        .col-letter { width: 18px; }
        .col-content { width: 30px; }

        /* Headers */
        .month-header {
            background-color: #4472C4; /* Bleu Excel */
            color: white;
            font-weight: bold;
            text-transform: uppercase;
        }
        .sub-header {
            background-color: #ffffff;
            font-weight: bold;
            font-size: 8px;
        }

        /* Styles Cellules */
        .weekend {
            background-color: #F2F2F2 !important; /* Gris clair WE */
            color: #999999;
        }
        .empty-day {
            background-color: #BFBFBF !important; /* Gris foncé (jours inexistants) */
            /* Motif hachuré simulé pour PDF */
            background-image: repeating-linear-gradient(45deg, #BFBFBF, #BFBFBF 5px, #A6A6A6 5px, #A6A6A6 10px);
        }
        
        .content-cell {
            font-weight: bold;
        }

        /* Styles Totaux en bas */
        .total-row td {
            border: 1px solid #444;
        }
        .label-cell {
            text-align: right;
            padding-right: 5px;
            font-weight: bold;
            border: none !important; /* Pas de bordure sur la zone légende */
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>PLANNING PRÉVISIONNEL : {{ $planning->title }}</h1>
        <h2>Période du {{ $planning->start_date->format('d/m/Y') }} au {{ $planning->end_date->format('d/m/Y') }}</h2>
    </div>

    <table>
        <thead>
            <tr>
                @foreach($grid as $monthData)
                    <th colspan="3" class="month-header">{{ $monthData['month_label'] }}</th>
                @endforeach
            </tr>
            <tr>
                @foreach($grid as $monthData)
                    <th class="sub-header col-day">J</th>
                    <th class="sub-header col-letter"></th>
                    <th class="sub-header col-content">C</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @for($day = 1; $day <= 31; $day++)
                <tr>
                    @foreach($grid as $monthData)
                        @php
                            $dayDTO = collect($monthData['days'])->first(fn($d) => $d->date->day === $day);
                        @endphp

                        @if($dayDTO)
                            @php $isWeekend = $dayDTO->type === 'weekend'; @endphp
                            
                            {{-- Cellule Jour --}}
                            <td class="{{ $isWeekend ? 'weekend' : '' }}">{{ $day }}</td>
                            
                            {{-- Cellule Lettre --}}
                            <td class="{{ $isWeekend ? 'weekend' : '' }}">{{ $dayDTO->dayLetter }}</td>
                            
                            {{-- Cellule Contenu (C) --}}
                            {{-- Priorité : Si WE, gris. Sinon, si couleur phase, couleur. --}}
                            <td class="content-cell {{ $isWeekend ? 'weekend' : '' }}"
                                style="@if(!$isWeekend && $dayDTO->color !== '#FFFFFF') background-color: {{ $dayDTO->color }}; @endif">
                                {{ $dayDTO->content }}
                            </td>
                        @else
                            {{-- Jour Inexistant (ex: 30 Fev) --}}
                            <td class="empty-day"></td>
                            <td class="empty-day"></td>
                            <td class="empty-day"></td>
                        @endif
                    @endforeach
                </tr>
            @endfor
            
            <tr><td colspan="{{ count($grid) * 3 }}" style="border:none; height:10px;"></td></tr>

            <tr class="total-row">
                @foreach($grid as $monthData)
                    <td colspan="2" class="label-cell" style="font-size:8px;">HEURES FORMATION</td>
                    <td style="font-weight:bold; background-color:#fff;">
                        {{-- Calcul PHP équivalent à SUM() --}}
                        @php
                            $sum = collect($monthData['days'])
                                ->filter(fn($d) => is_numeric($d->content))
                                ->sum('content');
                        @endphp
                        {{ $sum }}
                    </td>
                @endforeach
            </tr>

            @foreach($phases as $phase)
                <tr class="total-row">
                    @foreach($grid as $monthData)
                        <td colspan="2" class="label-cell" style="font-size:8px;">{{ mb_strtoupper($phase->name) }}</td>
                        <td style="font-weight:bold; background-color: {{ $phase->color }};">
                            {{-- Calcul PHP équivalent à COUNTIF() * HEURES --}}
                            @if($phase->code)
                                @php
                                    $count = collect($monthData['days'])
                                        ->filter(fn($d) => $d->content === $phase->code)
                                        ->count();
                                    $total = $count * $phase->hours_per_day;
                                @endphp
                                {{ $total > 0 ? $total : '-' }}
                            @else
                                -
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach

        </tbody>
    </table>

</body>
</html>