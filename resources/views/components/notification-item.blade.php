{{--
    One notification row (used in the bell dropdown and on the Notifications page).
    Clicking it sends PATCH /notifications/{id}/read, which marks it as read and opens its page.
--}}
@props(['notification'])

@php
    $data = $notification->data;
    [$icon, $tone] = match ($data['type'] ?? '') {
        'approved' => ['M4.5 12.75l6 6 9-13.5', 'bg-green-50 text-green-600'],
        'rejected' => ['M6 18L18 6M6 6l12 12', 'bg-red-50 text-red-600'],
        'overdue' => ['M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z', 'bg-red-50 text-red-600'],
        'new_request' => ['M12 4.5v15m7.5-7.5h-15', 'bg-amber-50 text-amber-600'],
        default => ['M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022', 'bg-primary-50 text-primary-600'],
    };
    $unread = $notification->read_at === null;
@endphp

<form method="POST" action="{{ route('notifications.read', $notification->id) }}">
    @csrf @method('PATCH')
    <button @class(['flex w-full items-start gap-3 px-4 py-3 text-left hover:bg-canvas', 'bg-primary-50/40' => $unread])>
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $tone }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
        </span>
        <span class="min-w-0 flex-1">
            <span @class(['block text-sm', 'font-semibold text-ink' => $unread, 'text-muted' => ! $unread])>{{ $data['message'] ?? 'Notification' }}</span>
            <span class="mt-0.5 block text-xs text-muted">{{ $notification->created_at->diffForHumans() }}</span>
        </span>
        @if ($unread)
            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-primary-600" title="Unread"></span>
        @endif
    </button>
</form>
