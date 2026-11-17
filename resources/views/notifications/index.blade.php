<x-app-layout>
    <x-slot name="title">Notifications</x-slot>

    <x-page-header title="Notifications" description="Approvals, rejections, overdue reminders, and new requests.">
        <x-slot name="actions">
            @if ($hasUnread)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf @method('PATCH')
                    <x-secondary-button type="submit">Mark all as read</x-secondary-button>
                </form>
            @endif
        </x-slot>
    </x-page-header>

    <x-card :padding="false" class="max-w-3xl">
        @if ($notifications->isEmpty())
            <x-empty-state title="No notifications yet" description="You'll be notified here when your reservations are approved or rejected." />
        @else
            <ul class="divide-y divide-line">
                @foreach ($notifications as $notification)
                    <li><x-notification-item :notification="$notification" /></li>
                @endforeach
            </ul>
            @if ($notifications->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $notifications->links() }}</div>
            @endif
        @endif
    </x-card>
</x-app-layout>
