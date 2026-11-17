{{--
    QR code as inline SVG (FR-E4). Usage: <x-qr-code :url="route('equipment.show', $item)" :size="160" />
    The QR code simply contains the URL; any phone camera opens it in the browser.
--}}
@props(['url', 'size' => 160])

<div {{ $attributes->merge(['class' => 'inline-block rounded-lg bg-white p-2']) }} role="img" aria-label="QR code for {{ $url }}">
    {!! SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size($size)->margin(0)->errorCorrection('M')->generate($url) !!}
</div>
