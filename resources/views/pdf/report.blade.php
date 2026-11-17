{{-- FR-H3: one report as a PDF (dompdf: plain tables + its own CSS). Receives $title, $headers, $rows from ReportService::table(). --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 14mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #141414; font-size: 8.5pt; }
        .header { border-bottom: 0.6mm solid #00638E; padding-bottom: 3mm; margin-bottom: 5mm; }
        .brand { color: #00638E; font-weight: bold; font-size: 9pt; letter-spacing: 0.5pt; text-transform: uppercase; }
        h1 { font-size: 14pt; margin: 1mm 0 0; }
        .meta { color: #64748B; font-size: 8pt; margin-top: 1mm; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #EEF6FA; color: #00364F; text-align: left; font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.3pt; padding: 2mm; border-bottom: 0.3mm solid #BFD8E3; }
        td { padding: 1.8mm 2mm; border-bottom: 0.2mm solid #E3E9ED; vertical-align: top; }
        tr:nth-child(even) td { background: #F9FBFC; }
        .empty { padding: 8mm; text-align: center; color: #64748B; }
        .footer { position: fixed; bottom: -8mm; left: 0; right: 0; font-size: 7pt; color: #64748B; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">LabLend · IT Department</div>
        <h1>{{ $title }}</h1>
        <div class="meta">Generated {{ now()->format('M d, Y g:i A') }} · {{ count($rows) }} row(s)</div>
    </div>

    <table>
        <thead>
            <tr>@foreach ($headers as $header)<th>{{ $header }}</th>@endforeach</tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @empty
                <tr><td class="empty" colspan="{{ count($headers) }}">No data for this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">LabLend – Equipment &amp; Room Reservation System</div>
</body>
</html>
