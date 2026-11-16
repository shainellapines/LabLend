{{--
    Two ways to use it:
      <x-status-badge label="Pending" color="amber" />
      <x-status-badge :status="$reservation->status" />   (once the enums exist in step 1.5; they provide label() and color())
--}}
@props(['status' => null, 'label' => null, 'color' => 'gray'])

@php
    if ($status !== null) {
        $label = $status->label();
        $color = $status->color();
    }

    // Full class names are written out so Tailwind can find them when it scans this file.
    $classes = [
        'gray'      => 'bg-slate-100 text-slate-700 ring-slate-500/20',
        'green'     => 'bg-green-50 text-green-700 ring-green-600/20',
        'amber'     => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'blue'      => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'indigo'    => 'bg-primary-50 text-primary-700 ring-primary-600/20',
        'red'       => 'bg-red-50 text-red-700 ring-red-600/20',
        'red-solid' => 'bg-red-600 text-white ring-red-600', // used for "Overdue"
    ][$color] ?? 'bg-slate-100 text-slate-700 ring-slate-500/20';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset $classes"]) }}>
    {{ $label }}
</span>
