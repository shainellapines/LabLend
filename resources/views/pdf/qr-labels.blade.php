{{--
    QR label sheet for dompdf (FR-E4). dompdf does not support Tailwind, flexbox, or grid,
    so this file uses a plain table and its own small <style> block.
    3 labels per row on A4; each QR code contains the item's URL.
--}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>LabLend QR labels</title>
    <style>
        @page { margin: 12mm 10mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #141414; font-size: 9pt; }
        table { width: 100%; border-collapse: separate; border-spacing: 3mm; }
        td { width: 33.33%; vertical-align: top; }
        .label { border: 0.4mm dashed #8CB9CC; border-radius: 2mm; padding: 3mm 3mm 4mm; height: 50mm; text-align: center; }
        .brand { font-size: 7pt; color: #00638E; font-weight: bold; letter-spacing: 0.5pt; text-transform: uppercase; }
        .qr { width: 28mm; height: 28mm; margin: 1.5mm auto; }
        .tag { font-family: DejaVu Sans Mono, monospace; font-size: 12pt; font-weight: bold; }
        .name { font-size: 8pt; color: #64748B; }
        .hint { font-size: 6.5pt; color: #64748B; margin-top: 1mm; }
    </style>
</head>
<body>
    <table>
        @foreach ($items->chunk(3) as $row)
            <tr>
                @foreach ($row as $item)
                    @php
                        // SVG QR code embedded as an image (data URI), which dompdf can draw.
                        $svg = SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(300)->margin(0)->errorCorrection('M')
                            ->generate(route('equipment.show', $item));
                    @endphp
                    <td>
                        <div class="label">
                            <div class="brand">LabLend · IT Department</div>
                            <img class="qr" src="data:image/svg+xml;base64,{{ base64_encode($svg) }}" alt="QR">
                            <div class="tag">{{ $item->asset_tag }}</div>
                            <div class="name">{{ \Illuminate\Support\Str::limit($item->name, 32) }}</div>
                            <div class="hint">Scan to see status &amp; history</div>
                        </div>
                    </td>
                @endforeach
                @for ($i = $row->count(); $i < 3; $i++)
                    <td></td>
                @endfor
            </tr>
        @endforeach
    </table>
</body>
</html>
