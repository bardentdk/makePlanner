<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Planning</title>
    <style>
        @page { margin: 5mm; } /* Marges fines */
        
        body {
            font-family: Arial, Helvetica, sans-serif; /* Police "Sheet" */
            font-size: 9px;
            color: #000;
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px;
            border: 1px solid #000;
            padding: 5px;
            background-color: #f9f9f9;
        }
        h1 { margin: 0; font-size: 14px; text-transform: uppercase; }
        h2 { margin: 3px 0 0; font-size: 11px; font-weight: normal; font-style: italic; }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        
        th, td {
            padding: 1px 2px; /* Padding très serré */
            text-align: center;
            height: 12px;
            vertical-align: middle;
            border-bottom: 1px solid #ccc; /* Lignes internes grises fines */
            border-right: 1px solid #ccc;
        }

        /* BORDURES STRUCTURELLES (Effet Miroir) */
        /* Chaque 3ème colonne (fin de mois) a une bordure noire épaisse à droite */
        td:nth-child(3n), th:nth-child(3n) {
            border-right: 2px solid #000 !important;
        }
        /* La première colonne (jours) a une bordure gauche */
        td:first-child, th:first-child {
            border-left: 2px solid #000 !important;
        }

        /* Largeurs */
        .col-day { width: 15px; }
        .col-letter { width: 15px; }
        .col-content { width: 25px; }

        /* Headers */
        .month-header {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            font-weight: bold;
            background-color: #fff; /* Souvent blanc dans les exports HTML bruts */
        }
        
        /* Cellules Spéciales */
        .weekend {
            background-color: #D9D9D9 !important; /* Gris moyen Excel */
            color: #000;
        }
        .empty-day {
            background-color: #808080 !important; /* Gris foncé */
        }
        .content-cell {
            font-weight: bold;
            font-size: 8px;
        }

        /* Totaux en bas */
        .total-row td {
            border-top: 1px solid #000;
        }
        .label-cell {
            text-align: right;
            padding-right: 5px;
            font-weight: bold;
            border-right: 1px solid #fff !important; /* Pas de ligne verticale sur la légende */
        }
    </style>
</head>
<body>

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
            </tr>
            <tr style="border-bottom: 2px solid #000;">
                @foreach($grid as $monthData)
                    <th>J</th>
                    <th></th>
                    <th>C</th>
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
                            
                            <td class="{{ $isWeekend ? 'weekend' : '' }}">{{ $day }}</td>
                            <td class="{{ $isWeekend ? 'weekend' : '' }}">{{ $dayDTO->dayLetter }}</td>
                            
                            <td class="content-cell {{ $isWeekend ? 'weekend' : '' }}"
                                style="@if(!$isWeekend && $dayDTO->color !== '#FFFFFF') background-color: {{ $dayDTO->color }}; @endif">
                                {{ $dayDTO->content }}
                            </td>
                        @else
                            <td class="empty-day"></td>
                            <td class="empty-day"></td>
                            <td class="empty-day"></td>
                        @endif
                    @endforeach
                </tr>
            @endfor
            
            <tr><td colspan="{{ count($grid) * 3 }}" style="height:5px; border:none;"></td></tr>

            <tr>
                @foreach($grid as $monthData)
                    <td colspan="2" class="label-cell">Total</td>
                    <td style="font-weight:bold; border: 1px solid #000;">
                        @php
                            $sum = collect($monthData['days'])->filter(fn($d) => is_numeric($d->content))->sum('content');
                        @endphp
                        {{ $sum }}
                    </td>
                @endforeach
            </tr>
            
            @foreach($phases as $phase)
                <tr>
                    @foreach($grid as $monthData)
                        <td colspan="2" class="label-cell">{{ substr($phase->name, 0, 5) }}</td>
                        <td style="font-weight:bold; border: 1px solid #000; background-color: {{ $phase->color }};">
                            @if($phase->code)
                                @php
                                    $count = collect($monthData['days'])->filter(fn($d) => $d->content === $phase->code)->count();
                                    $total = $count * $phase->hours_per_day;
                                @endphp
                                {{ $total > 0 ? $total : '' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach

        </tbody>
    </table>

</body>
</html>