{{-- FR-R10: printable reservation slip with signature lines (dompdf: plain tables + its own CSS). --}}
@php($r = $reservation)
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $r->reference_no }}</title>
    <style>
        @page { margin: 16mm 15mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #141414; font-size: 9.5pt; }
        .top { width: 100%; border-bottom: 0.6mm solid #00638E; padding-bottom: 3mm; margin-bottom: 6mm; }
        .brand { color: #00638E; font-weight: bold; font-size: 13pt; }
        .sub { color: #64748B; font-size: 8pt; }
        .ref { font-family: DejaVu Sans Mono, monospace; font-size: 15pt; font-weight: bold; text-align: right; }
        .status { display: inline-block; padding: 1mm 3mm; border-radius: 3mm; background: #EEF6FA; color: #00557A; font-weight: bold; font-size: 8pt; text-transform: uppercase; }
        .overdue { background: #DC2626; color: #FFFFFF; }
        h2 { font-size: 9pt; text-transform: uppercase; letter-spacing: 0.4pt; color: #00638E; margin: 6mm 0 2mm; }
        table { width: 100%; border-collapse: collapse; }
        .info td { padding: 1.5mm 0; vertical-align: top; }
        .info td.label { width: 32mm; color: #64748B; }
        .items th { background: #EEF6FA; text-align: left; font-size: 8pt; padding: 2mm; border-bottom: 0.3mm solid #BFD8E3; }
        .items td { padding: 2mm; border-bottom: 0.2mm solid #E3E9ED; }
        .mono { font-family: DejaVu Sans Mono, monospace; }
        .signatures { margin-top: 16mm; }
        .signatures td { width: 50%; padding: 0 6mm; text-align: center; vertical-align: bottom; }
        .line { border-top: 0.3mm solid #141414; padding-top: 1.5mm; font-size: 8.5pt; }
        .muted { color: #64748B; font-size: 8pt; }
    </style>
</head>
<body>
    <table class="top">
        <tr>
            <td>
                <div class="brand">LabLend</div>
                <div class="sub">IT Department · Equipment &amp; Room Reservation Slip</div>
            </td>
            <td class="ref">
                {{ $r->reference_no }}<br>
                <span class="status {{ $r->isOverdue() ? 'overdue' : '' }}">{{ $r->isOverdue() ? 'Overdue' : $r->status->label() }}</span>
            </td>
        </tr>
    </table>

    <h2>Borrower</h2>
    <table class="info">
        <tr><td class="label">Name</td><td>{{ $r->user->name }}</td></tr>
        <tr><td class="label">ID number</td><td>{{ $r->user->id_number }} · {{ $r->user->role->label() }}{{ $r->user->department ? ' · ' . $r->user->department : '' }}</td></tr>
        <tr><td class="label">Contact</td><td>{{ $r->user->email }}{{ $r->user->phone ? ' · ' . $r->user->phone : '' }}</td></tr>
    </table>

    <h2>Schedule</h2>
    <table class="info">
        <tr><td class="label">Purpose</td><td>{{ $r->purpose }}</td></tr>
        <tr><td class="label">From</td><td>{{ $r->start_at->format('l, M d, Y g:i A') }}</td></tr>
        <tr><td class="label">Until</td><td>{{ $r->end_at->format('l, M d, Y g:i A') }}</td></tr>
        <tr><td class="label">Room</td><td>{{ $r->room ? $r->room->name . ' (' . $r->room->code . ')' : 'None' }}</td></tr>
        @if ($r->approver && $r->approved_at)
            <tr><td class="label">Approved</td><td>{{ $r->approved_at->format('M d, Y g:i A') }} by {{ $r->approver->name }}</td></tr>
        @endif
    </table>

    <h2>Items</h2>
    <table class="items">
        <thead>
            <tr><th style="width: 28mm;">Asset tag</th><th>Item</th><th>Category</th><th>Condition out</th><th>Condition in</th></tr>
        </thead>
        <tbody>
            @forelse ($r->equipment as $item)
                <tr>
                    <td class="mono">{{ $item->asset_tag }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->category->name }}</td>
                    <td>{{ ucfirst($item->pivot->condition_out ?? '—') }}</td>
                    <td>{{ ucfirst($item->pivot->condition_in ?? '—') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Room only, no equipment.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="muted" style="margin-top: 4mm;">
        The borrower is responsible for the items above until they are returned to the lab custodian in the same condition.
    </p>

    <table class="signatures">
        <tr>
            <td><div class="line">Borrower: {{ $r->user->name }}</div><div class="muted">Signature over printed name / date</div></td>
            <td><div class="line">Lab custodian</div><div class="muted">Signature over printed name / date</div></td>
        </tr>
    </table>

    <p class="muted" style="margin-top: 10mm; text-align: center;">Printed {{ now()->format('M d, Y g:i A') }} · LabLend</p>
</body>
</html>
