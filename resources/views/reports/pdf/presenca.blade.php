<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Presença — {{ $hospital->name }}</title>
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
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th { background: #E0F7F5; color: #01282B; text-align: left; padding: 6px 8px; font-size: 9px; text-transform: uppercase; }
        td { padding: 5px 8px; border-bottom: 1px solid #eef2f2; }
        tr:nth-child(even) td { background: #fafcfc; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 8px 28px; font-size: 9px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
        .footer .brand { color: #02BBB1; font-weight: bold; }
        .status { font-size: 9px; padding: 1px 6px; border-radius: 8px; }
        .status-completo { background: #EEF9E3; color: #57A319; }
        .status-andamento { background: #fef3c7; color: #b45309; }
        .status-sem { background: #F3F4F6; color: #6B7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Doctor<span class="accent">Turn</span> — Presença</h1>
        <p>{{ $hospital->name }} · {{ $from->format('d/m/Y') }} a {{ $to->format('d/m/Y') }} · gerado em {{ now()->format('d/m/Y H:i') }}</p>
        <div class="bar"></div>
    </div>

    <div class="content">
        <h2>Check-in / Check-out</h2>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Turno</th>
                    <th>Médico</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($shifts as $shift)
                    @php
                        $in = $shift->checkins->firstWhere('type', 'in');
                        $out = $shift->checkins->firstWhere('type', 'out');
                    @endphp
                    <tr>
                        <td>{{ $shift->date->format('d/m/Y') }}</td>
                        <td>{{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}</td>
                        <td>{{ $shift->doctor?->name ?? '—' }}</td>
                        <td>{{ $in ? $in->checked_at->format('H:i') : '—' }}</td>
                        <td>{{ $out ? $out->checked_at->format('H:i') : '—' }}</td>
                        <td>
                            @if ($in && $out)
                                <span class="status status-completo">Completo</span>
                            @elseif ($in)
                                <span class="status status-andamento">Em andamento</span>
                            @else
                                <span class="status status-sem">Sem registro</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">Nenhum plantão no período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <span class="brand">DoctorTurn</span> — Gestão de escalas médicas · {{ $hospital->name }}
    </div>
</body>
</html>
