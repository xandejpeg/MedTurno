<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Aderência — {{ $tender->title }}</title>
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
        .status-pronto { background: #EEF9E3; color: #57A319; }
        .status-parcial { background: #fef3c7; color: #b45309; }
        .status-faltando { background: #fee2e2; color: #b91c1c; }
        .status-aplicacao { background: #E0F2FE; color: #0369A1; }
        .progress { background: #e5e7eb; border-radius: 4px; height: 8px; margin-top: 4px; }
        .progress-bar { background: #02BBB1; border-radius: 4px; height: 100%; }
        .category { font-size: 11px; font-weight: bold; color: #01282B; margin: 12px 0 4px; padding: 4px 8px; background: #E0F7F5; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Doctor<span class="accent">Turn</span> — Aderência a Licitação</h1>
        <p>{{ $tender->title }} · {{ $tender->orgao }} · gerado em {{ now()->format('d/m/Y H:i') }}</p>
        <div class="bar"></div>
    </div>

    <div class="content">
        <h2>Progresso geral</h2>
        <p><strong>{{ $tender->progress }}%</strong> de aderência</p>
        <div class="progress"><div class="progress-bar" style="width: {{ $tender->progress }}%"></div></div>

        @php
            $requirements = $tender->requirements()->orderBy('sort')->get()->groupBy('category');
        @endphp

        @foreach ($requirements as $category => $items)
            <div class="category">{{ $category ?? 'Geral' }}</div>
            <table>
                <thead>
                    <tr>
                        <th>Requisito</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>{{ $item->title }}</td>
                            <td>
                                <span class="status status-{{ $item->status }}">{{ $item->statusLabel() }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    </div>

    <div class="footer">
        <span class="brand">DoctorTurn</span> — Gestão de escalas médicas · {{ $tender->title }}
    </div>
</body>
</html>
