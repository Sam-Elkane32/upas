<x-app-layout>
    <x-slot name="header"><div></div></x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <div>
            <h1 class="text-xl font-bold text-gray-800">{{ __('Ticket alerts') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ __('Repair ticket submissions notify developer accounts here. Open an item to jump to the ticket.') }}</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm divide-y divide-gray-100">
            @forelse($notifications as $n)
                @php
                    $data = $n->data ?? [];
                    $label = $data['message'] ?? ($data['title'] ?? __('Repair ticket'));
                @endphp
                <div class="flex items-start justify-between gap-3 p-4 {{ $n->read_at ? 'bg-white' : 'bg-indigo-50/40' }}">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900">{{ $label }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $n->created_at?->diffForHumans() ?? '' }}</p>
                    </div>
                    <a href="{{ route('messaging.developer-notifications.open', ['id' => $n->id, 'audience' => 'developers']) }}"
                       class="shrink-0 inline-flex items-center rounded-lg border border-indigo-300 bg-white px-3 py-1.5 text-sm font-medium text-indigo-700 hover:bg-indigo-50">
                        {{ __('Open') }}
                    </a>
                </div>
            @empty
                <div class="p-10 text-center text-sm text-gray-500">
                    {{ __('No ticket alerts yet.') }}
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="text-sm text-gray-600">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>

    <script>
    (function () {
        document.body.classList.add('messaging-page');
        window.addEventListener('beforeunload', function () {
            document.body.classList.remove('messaging-page');
        });
    })();
    </script>
</x-app-layout>
