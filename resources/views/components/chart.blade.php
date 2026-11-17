{{--
    A Chart.js chart (drawn by the "chart" Alpine component in resources/js/app.js).
    Usage: <x-chart type="line" label="Reservations" :labels="[...]" :values="[...]" class="h-64" />
    A hidden table with the same numbers is included for screen readers.
--}}
@props(['type' => 'bar', 'label', 'labels' => [], 'values' => []])

<div {{ $attributes->merge(['class' => 'relative h-64']) }}
     x-data="chart({ type: @js($type), label: @js($label), labels: @js($labels), values: @js($values) })">
    <canvas x-ref="canvas" role="img" aria-label="{{ $label }} chart"></canvas>

    <table class="sr-only">
        <caption>{{ $label }}</caption>
        <tbody>
            @foreach ($labels as $i => $name)
                <tr><th scope="row">{{ $name }}</th><td>{{ $values[$i] ?? 0 }}</td></tr>
            @endforeach
        </tbody>
    </table>
</div>
