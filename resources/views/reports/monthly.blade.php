<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório DoctorTurn — {{ $monthLabel }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        .header { background: #01282B; color: #fff; padding: 22px 28px; }
        .header h1 { font-size: 20px; }
        .header h1 .accent { color: #6BC320; }
        .header p { color: #9fd8d4; font-size: 11px; margin-top: 4px; }
        .header .bar { height: 3px; width: 60px; background: #02BBB1; margin-top: 10px; }
        .content { padding: 20px 28px; }
        h2 { font-size: 13px; color: #01282B; border-bottom: 2px solid #02BBB1; padding-bottom: 4px; margin: 18px 0 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th { background: #E0F7F5; color: #01282B; text-align: left; padding: 6px 8px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.4px; }
        td { padding: 5px 8px; border-bottom: 1px solid #eef2f2; }
        tr:nth-child(even) td { background: #fafcfc; }
        .kpi-table td { border: none; padding: 0 6px 0 0; }
        .kpi { background: #f4fbfa; border-left: 3px solid #02BBB1; padding: 10px 12px; }
        .kpi.green { border-left-color: #6BC320; }
        .kpi.warn { border-left-color: #f59e0b; }
        .kpi .label { font-size: 9px; text-transform: uppercase; color: #6b7280; letter-spacing: 0.4px; }
        .kpi .value { font-size: 16px; font-weight: bold; color: #01282B; margin-top: 3px; }
        .right { text-align: right; }
        .badge { font-size: 9px; padding: 1px 6px; border-radius: 8px; }
        .badge-ok { background: #EEF9E3; color: #57A319; }
        .badge-warn { background: #fef3c7; color: #b45309; }
        .badge-bad { background: #fee2e2; color: #b91c1c; }
        .muted { color: #9ca3af; }
        .total-row td { background: #01282B !important; color: #fff; font-weight: bold; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 8px 28px; font-size: 9px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
        .footer .brand { color: #02BBB1; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Doctor<span class="accent">Turn</span> — Relatório mensal</h1>
        <p>{{ $hospital->name }} · <span style="text-transform: capitalize">{{ $monthLabel }}</span> · gerado em {{ $generatedAt->format('d/m/Y H:i') }}</p>
        <div class="bar"></div>
    </div>

    <div class="content">
        @if (in_array('resumo', $sections, true))
            <h2>Resumo geral</h2>
            <table class="kpi-table">
                <tr>
                    <td width="25%"><div class="kpi"><div class="label">Custo previsto</div><div class="value">R$ {{ number_format($custoPrevisto, 2, ',', '.') }}</div></div></td>
                    <td width="25%"><div class="kpi green"><div class="label">Confirmado/concluído</div><div class="value">R$ {{ number_format($custoConfirmado, 2, ',', '.') }}</div></div></td>
                    <td width="25%"><div class="kpi"><div class="label">Horas de plantão</div><div class="value">{{ number_format($horas, 0, ',', '.') }}h</div></div></td>
                    <td width="25%"><div class="kpi {{ $uncovered->count() > 0 ? 'warn' : '' }}"><div class="label">Cobertura</div><div class="value">{{ $assigned->count() }}/{{ $assigned->count() + $uncovered->count() }} plantões</div></div></td>
                </tr>
            </table>
            <p class="muted" style="margin-top: 4px;">
                {{ $assigned->count() }} plantão(ões) atribuído(s), {{ $uncovered->count() }} descoberto(s),
                {{ $shifts->count() - $assigned->count() - $uncovered->count() }} cancelado(s)/não cumprido(s) no período.
            </p>
        @endif

        @if (in_array('financeiro', $sections, true))
            <h2>Financeiro por médico</h2>
            <table>
                <thead>
                    <tr>
                        <th>Médico</th>
                        <th class="right">Plantões</th>
                        <th class="right">A pagar (confirmados)</th>
                        <th class="right">Previsto total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($doctors as $row)
                        <tr>
                            <td>{{ $row['doctor']->name }}</td>
                            <td class="right">{{ $row['payableCount'] }}/{{ $row['count'] }}</td>
                            <td class="right">R$ {{ number_format($row['payableTotal'], 2, ',', '.') }}</td>
                            <td class="right">R$ {{ number_format($row['total'], 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">Nenhum plantão atribuído.</td></tr>
                    @endforelse
                    @if ($doctors->isNotEmpty())
                        <tr class="total-row">
                            <td>Total</td>
                            <td class="right">{{ $doctors->sum('payableCount') }}/{{ $doctors->sum('count') }}</td>
                            <td class="right">R$ {{ number_format($doctors->sum('payableTotal'), 2, ',', '.') }}</td>
                            <td class="right">R$ {{ number_format($doctors->sum('total'), 2, ',', '.') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @endif

        @if (in_array('plantoes', $sections, true))
            <h2>Detalhamento de plantões</h2>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Horário</th>
                        <th>Quadro</th>
                        <th>Médico</th>
                        <th>Status</th>
                        <th class="right">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shifts as $shift)
                        <tr>
                            <td>{{ $shift->date->format('d/m') }} ({{ $shift->date->translatedFormat('D') }})</td>
                            <td>{{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}</td>
                            <td>{{ $shift->schedule->board?->name ?? $shift->hospital->name }}</td>
                            <td>{{ $shift->doctor?->name ?? '—' }}</td>
                            <td>
                                <span class="badge
                                    @if (in_array($shift->status, $payable, true)) badge-ok
                                    @elseif (in_array($shift->status, $dead, true) || $shift->user_id === null) badge-bad
                                    @else badge-warn @endif">
                                    {{ $shift->status->label() }}
                                </span>
                            </td>
                            <td class="right {{ in_array($shift->status, $dead, true) ? 'muted' : '' }}">R$ {{ number_format((float) $shift->amount, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Nenhum plantão publicado no período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif

        @if (in_array('descobertos', $sections, true))
            <h2>Plantões descobertos (sem médico)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Horário</th>
                        <th>Quadro</th>
                        <th class="right">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($uncovered as $shift)
                        <tr>
                            <td>{{ $shift->date->format('d/m') }} ({{ $shift->date->translatedFormat('D') }})</td>
                            <td>{{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}</td>
                            <td>{{ $shift->schedule->board?->name ?? $shift->hospital->name }}</td>
                            <td class="right">R$ {{ number_format((float) $shift->amount, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">Nenhum plantão descoberto — escala 100% coberta. ✔</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif

        @if (in_array('trocas', $sections, true))
            <h2>Trocas do mês</h2>
            <table>
                <thead>
                    <tr>
                        <th>Plantão</th>
                        <th>De</th>
                        <th>Para</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transfers as $transfer)
                        <tr>
                            <td>{{ $transfer->shift->date->format('d/m') }} · {{ $transfer->shift->starts_at->format('H:i') }}–{{ $transfer->shift->ends_at->format('H:i') }} · {{ $transfer->shift->schedule->board?->name ?? $transfer->shift->hospital->name }}</td>
                            <td>{{ $transfer->fromDoctor?->name ?? '—' }}</td>
                            <td>{{ $transfer->toDoctor?->name ?? '—' }}</td>
                            <td>
                                <span class="badge
                                    @if ($transfer->status === \App\Enums\TransferStatus::Aprovada) badge-ok
                                    @elseif ($transfer->status->isActive()) badge-warn
                                    @else badge-bad @endif">
                                    {{ $transfer->status->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">Nenhuma troca registrada no período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif
    </div>

    <div class="footer">
        <span class="brand">DoctorTurn</span> · Gestão de escalas médicas · {{ $hospital->name }} · relatório de {{ $monthLabel }}
    </div>
</body>
</html>
