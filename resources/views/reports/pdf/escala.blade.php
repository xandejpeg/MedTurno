<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Escala — {{ $schedule->hospital->name }} — {{ $schedule->monthLabel() }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        .header { background: #01282B; color: #fff; padding: 22px 28px; }
        .header h1 { font-size: 20px; }
        .header h1 .accent { color: #6BC320; }
        .header p { color: #9fd8d4; font-size: 11px; margin-top: 4px; }
        .header .bar { height: 3px; width: 60px; background: #02BBB1; margin-top: 10px; }
        .content { padding: 20px 28px; }
        h2 { font-size: 13px; color: #01282B; border-bottom: 2px solid #02BBB1; padding-bottom: 4px; margin: 18px 0 10px; }
        .day { display: inline-block; width: 13.5%; vertical-align: top; margin-bottom: 8px; }
        .day-header { font-size: 9px; font-weight: bold; color: #01282B; margin-bottom: 2px; }
        .shift { font-size: 8px; padding: 2px 4px; margin: 1px 0; border-radius: 3px; }
        .shift-dia { background: #FEF3C7; color: #92400E; }
        .shift-noite { background: #E0E7FF; color: #3730A3; }
        .shift-vazio { background: #F3F4F6; color: #6B7280; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 8px 28px; font-size: 9px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
        .footer .brand { color: #02BBB1; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Doctor<span class="accent">Turn</span> — Escala</h1>
        <p>{{ $schedule->hospital->name }} · {{ $schedule->monthLabel() }} · v{{ $schedule->version }} · gerado em {{ now()->format('d/m/Y H:i') }}</p>
        <div class="bar"></div>
    </div>

    <div class="content">
        @php
            $shifts = $schedule->shifts()->with('doctor')->get()->groupBy(fn ($s) => $s->date->toDateString());
            $start = \Illuminate\Support\Carbon::create($schedule->year, $schedule->month, 1);
            $end = $start->copy()->endOfMonth();
            $cursor = $start->copy();
        @endphp

        <h2>Calendário</h2>
        <div>
            @while ($cursor->lte($end))
                @php $dayShifts = $shifts->get($cursor->toDateString(), collect()); @endphp
                <div class="day">
                    <div class="day-header">{{ $cursor->format('d') }} ({{ ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'][$cursor->dayOfWeek] }})</div>
                    @foreach (['dia' => '07–19h', 'noite' => '19–07h'] as $period => $hours)
                        @php $shift = $dayShifts->firstWhere('period', $period); @endphp
                        <div class="shift shift-{{ $period }} {{ $shift && $shift->doctor ? '' : 'shift-vazio' }}">
                            {{ $hours }}<br>
                            @if ($shift && $shift->doctor)
                                {{ \Illuminate\Support\Str::of($shift->doctor->name)->explode(' ')->first() }}
                            @else
                                —
                            @endif
                        </div>
                    @endforeach
                </div>
                @php $cursor->addDay(); @endphp
            @endwhile
        </div>
    </div>

    <div class="footer">
        <span class="brand">DoctorTurn</span> — Gestão de escalas médicas · {{ $schedule->hospital->name }} · {{ $schedule->monthLabel() }}
    </div>
</body>
</html>
