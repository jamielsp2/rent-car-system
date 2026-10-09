@use('App\Enums\BookingStatus')
@use('App\Enums\PaymentStatus')
@use('App\Filament\Clusters\Reservas\Pages\TodayHandovers')
@php
    /** @var \App\Models\Booking $booking */
    $rental = $booking->rental;
    $contract = $booking->contract;
    $time = $kind === 'delivery' ? $rental?->rental_start_date : $rental?->rental_end_date;
    $phone = preg_replace('/\D+/', '', (string) $booking->customer_phone);
    $editUrl = \App\Filament\Resources\Bookings\BookingResource::getUrl('edit', ['record' => $booking]);
    $lateDays = $kind === 'overdue' ? max(1, (int) $time?->diffInDays(\App\Support\LocalTime::now())) : 0;

    // What has been done for this handover, in order.
    $steps = $kind === 'delivery'
        ? [
            'Confirmada' => $booking->status !== BookingStatus::Pending,
            'Pagada' => $booking->payment?->status === PaymentStatus::Completed,
            'Contrato' => (bool) $contract?->isSigned(),
            'Entregado' => TodayHandovers::delivered($booking),
        ]
        : [
            'Con el cliente' => true,
            'Revisión' => (bool) $contract?->isReturned(),
            'Completada' => $booking->status === BookingStatus::Completed,
        ];
    $done = $kind === 'delivery' ? TodayHandovers::delivered($booking) : ($kind === 'return' && TodayHandovers::returned($booking));
@endphp
<li class="relative flex gap-4 py-4">
    {{-- Time and timeline dot --}}
    <div class="flex w-16 shrink-0 flex-col items-center">
        @if ($kind === 'overdue')
            <span class="text-sm font-bold tabular-nums text-rose-600">{{ $time?->format('d/m') }}</span>
            <span class="text-[11px] font-medium text-rose-500">{{ $lateDays }} {{ $lateDays === 1 ? 'día' : 'días' }} tarde</span>
        @else
            <span class="text-sm font-bold tabular-nums text-gray-950">{{ $time?->format('h:i') }}</span>
            <span class="text-[11px] font-medium uppercase text-gray-400">{{ $time?->format('A') }}</span>
        @endif
        <span @class([
            'mt-2 flex size-6 items-center justify-center rounded-full ring-4 ring-white',
            'bg-emerald-500 text-white' => $done,
            'bg-rose-500 text-white' => $kind === 'overdue',
            'bg-white text-gray-300 border-2 border-gray-200' => ! $done && $kind !== 'overdue',
        ])>
            @if ($done)
                <x-filament::icon icon="heroicon-m-check" class="size-3.5" />
            @elseif ($kind === 'overdue')
                <x-filament::icon icon="heroicon-m-exclamation-triangle" class="size-3.5" />
            @endif
        </span>
        @unless ($last ?? true)
            <span class="mt-1 w-px flex-1 bg-gray-200"></span>
        @endunless
    </div>

    {{-- Booking --}}
    <div @class(['min-w-0 flex-1 rounded-xl border p-3 transition', 'border-emerald-200 bg-emerald-50/40' => $done, 'border-gray-200 bg-white hover:border-gray-300 hover:shadow-sm' => ! $done])>
        <div class="flex gap-3">
            @if ($rental?->car?->cover_url)
                <img src="{{ $rental->car->cover_url }}" alt="" loading="lazy" class="h-16 w-24 shrink-0 rounded-lg object-cover ring-1 ring-gray-950/5">
            @else
                <span class="flex h-16 w-24 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-300">
                    <x-filament::icon icon="heroicon-o-truck" class="size-6" />
                </span>
            @endif
            <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-2">
                    <p class="truncate font-semibold text-gray-950">{{ $rental?->car_name }}</p>
                    @if ($rental?->car?->license_plate)
                        <span class="shrink-0 rounded border border-gray-300 bg-gray-50 px-1.5 py-0.5 font-mono text-[11px] font-semibold tracking-wider text-gray-700">{{ $rental->car->license_plate }}</span>
                    @endif
                </div>
                <p class="mt-0.5 truncate text-sm text-gray-600">
                    <x-filament::icon icon="heroicon-m-user" class="inline size-3.5 text-gray-400" />
                    {{ $booking->customer_name }}
                    @if ($booking->customer_phone)
                        · <a href="tel:{{ $booking->customer_phone }}" class="hover:text-primary-600 hover:underline">{{ $booking->customer_phone }}</a>
                    @endif
                </p>
                <p class="mt-0.5 text-xs text-gray-400">
                    <a href="{{ $editUrl }}" class="font-medium text-primary-600 hover:underline">{{ $booking->booking_number }}</a>
                    @if ($rental)
                        · {{ $rental->rental_start_date->translatedFormat('j M') }} → {{ $rental->rental_end_date->translatedFormat('j M') }} ({{ $rental->days() }} {{ $rental->days() === 1 ? 'día' : 'días' }})
                    @endif
                </p>
            </div>
        </div>

        @if ($kind !== 'overdue')
            {{-- Progress of the handover --}}
            <ol class="mt-3 flex items-center gap-1">
                @foreach ($steps as $label => $ok)
                    <li class="flex min-w-0 flex-1 items-center gap-1">
                        <span @class([
                            'flex min-w-0 flex-1 items-center justify-center gap-1 rounded-md px-1.5 py-1 text-[11px] font-medium',
                            'bg-emerald-100 text-emerald-800' => $ok,
                            'bg-gray-100 text-gray-500' => ! $ok,
                        ])>
                            <x-filament::icon :icon="$ok ? 'heroicon-m-check-circle' : 'heroicon-o-ellipsis-horizontal-circle'" class="size-3.5 shrink-0" />
                            <span class="truncate">{{ $label }}</span>
                        </span>
                    </li>
                @endforeach
            </ol>
        @endif

        <div class="mt-3 flex flex-wrap items-center justify-end gap-2">
            @if ($phone)
                <x-filament::button tag="a" href="https://wa.me/{{ $phone }}" target="_blank" color="gray" size="xs" icon="heroicon-o-chat-bubble-left-right">WhatsApp</x-filament::button>
            @endif

            @if ($kind === 'delivery')
                @if ($booking->status === BookingStatus::Delivered)
                    <x-filament::button tag="a" :href="route('contracts.delivery', $booking)" target="_blank" color="success" size="xs" icon="heroicon-o-document-check">Ver contrato</x-filament::button>
                @elseif ($contract?->isSigned())
                    <x-filament::button wire:click="markDelivered({{ $booking->id }})" wire:confirm="¿Marcar {{ $booking->booking_number }} como entregado?" size="xs" icon="heroicon-o-key">Marcar entregado</x-filament::button>
                @else
                    <x-filament::button tag="a" :href="route('contracts.delivery', $booking)" target="_blank" size="xs" icon="heroicon-o-pencil-square">Firmar contrato</x-filament::button>
                @endif
            @else
                @if ($contract?->isReturned())
                    <x-filament::button tag="a" :href="route('contracts.return', $booking)" target="_blank" color="success" size="xs" icon="heroicon-o-document-check">Ver devolución</x-filament::button>
                @elseif ($contract?->isSigned())
                    <x-filament::button tag="a" :href="route('contracts.return', $booking)" target="_blank" color="warning" size="xs" icon="heroicon-o-arrow-uturn-left">Revisión de devolución</x-filament::button>
                @else
                    <x-filament::button tag="a" :href="$editUrl" color="gray" size="xs">Ver reserva</x-filament::button>
                @endif
            @endif
        </div>
    </div>
</li>
