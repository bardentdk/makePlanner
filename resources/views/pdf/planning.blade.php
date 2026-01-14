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
        
        /* MODIFICATION ICI : BLANC POUR LE WE */
        .weekend { 
            background-color: #F2F2F2 !important; 
            color: #444; /* Optionnel : Chiffre un peu moins noir pour adoucir */
        } 

        .empty-day { background-color: #808080 !important; }
        .content-cell { font-weight: bold; font-size: 8px; }

        .col-day { width: 15px; }
        .col-letter { width: 15px; }
        .col-content { width: 25px; }
        .total-row td { border-top: 2px solid #000 !important; }
        .label-cell { text-align: right; padding-right: 5px; font-weight: bold; border-right: 1px solid #fff !important; border-bottom: none !important; border-left: none !important; }
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
                </tr>
            @endfor
            <tr><td colspan="{{ count($grid) * 3 }}" style="height:5px; border:none;"></td></tr>
            <tr class="total-row">
                @foreach($grid as $monthData)
                    <td colspan="2" class="label-cell">Total Heures</td>
                    <td style="font-weight:bold; border: 1px solid #000;">
                        @php echo collect($monthData['days'])->filter(fn($d) => is_numeric($d->content))->sum('content'); @endphp
                    </td>
                @endforeach
            </tr>
            @foreach($phases->unique('code') as $phase)
                <tr>
                    @foreach($grid as $monthData)
                        <td colspan="2" class="label-cell">{{ substr($phase->name, 0, 10) }}</td>
                        <td style="font-weight:bold; border: 1px solid #000; background-color: {{ $phase->color }};">
                            @if($phase->code)
                                @php
                                    $cnt = collect($monthData['days'])->filter(fn($d) => $d->content === $phase->code)->count();
                                    $tot = $cnt * $phase->hours_per_day;
                                @endphp
                                {{ $tot > 0 ? $tot : '' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>