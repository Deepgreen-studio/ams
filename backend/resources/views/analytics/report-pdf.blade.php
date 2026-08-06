<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { color: #64748b; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; }
        th { background: #f8fafc; font-weight: 600; }
        .groups { margin-top: 18px; }
    </style>
</head>
<body>
    <h1>{{ $report->name }}</h1>
    <div class="meta">
        Generated {{ $dataset['meta']['generated_at'] ?? now()->toDateTimeString() }}
        · Type {{ $dataset['meta']['report_type'] ?? $report->report_type }}
        · Rows {{ $dataset['meta']['row_count'] ?? 0 }}
        @if(!empty($printMode)) · Print @endif
    </div>

    <table>
        <thead>
            <tr>
                @foreach($dataset['columns'] as $column)
                    <th>{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($dataset['rows'] as $row)
                <tr>
                    @foreach($dataset['columns'] as $column)
                        <td>{{ $row[$column['key']] ?? '' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(count($dataset['columns']), 1) }}">No rows</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if(!empty($dataset['groups']))
        <div class="groups">
            <h2>Groups</h2>
            <table>
                <thead>
                    <tr>
                        <th>Group</th>
                        <th>Count</th>
                        <th>Sum</th>
                        <th>Avg</th>
                        <th>Aggregate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dataset['groups'] as $group)
                        <tr>
                            <td>{{ $group['group_key'] }}</td>
                            <td>{{ $group['count'] }}</td>
                            <td>{{ $group['sum'] }}</td>
                            <td>{{ $group['avg'] }}</td>
                            <td>{{ $group['aggregate_value'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</body>
</html>
