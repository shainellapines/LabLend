{{-- Shows session('success') / session('error') set by controllers: return redirect()->...->with('success', '...'); --}}
@foreach (['success' => 'border-green-200 bg-green-50 text-green-800', 'error' => 'border-red-200 bg-red-50 text-red-800'] as $type => $classes)
    @if (session($type))
        <div x-data="{ show: true }" x-show="show" x-transition
             @if ($type === 'success') x-init="setTimeout(() => show = false, 5000)" @endif
             class="mb-6 flex items-start justify-between gap-4 rounded-lg border px-4 py-3 text-sm {{ $classes }}"
             role="alert">
            <span>{{ session($type) }}</span>
            <button type="button" @click="show = false" class="opacity-60 hover:opacity-100">
                <span class="sr-only">Dismiss</span>&times;
            </button>
        </div>
    @endif
@endforeach
