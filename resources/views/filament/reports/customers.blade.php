<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Mejores clientes</x-slot>
        <x-slot name="description">Por monto facturado en el período</x-slot>

        @if ($customers->isEmpty())
            <p class="py-8 text-center text-sm text-gray-500">Sin reservas en este período.</p>
        @else
            <ul class="space-y-4">
                @foreach ($customers as $i => $customer)
                    <li class="flex items-center gap-4">
                        <span @class([
                            'flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                            'bg-gradient-to-br from-slate-900 to-[#206bc4] text-white' => $i === 0,
                            'bg-slate-100 text-slate-600' => $i > 0,
                        ])>{{ $customer['initials'] ?: '?' }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between gap-3">
                                @if ($customer['url'])
                                    <a href="{{ $customer['url'] }}" class="truncate font-medium text-gray-950 hover:text-primary-600">{{ $customer['name'] }}</a>
                                @else
                                    <span class="truncate font-medium text-gray-950">{{ $customer['name'] }}</span>
                                @endif
                                <span class="shrink-0 font-semibold tabular-nums text-gray-950">{{ $customer['revenue'] }}</span>
                            </div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-gradient-to-r from-[#206bc4] to-sky-400" style="width: {{ $customer['share'] }}%"></div>
                            </div>
                            <p class="mt-1 truncate text-xs text-gray-500">
                                {{ $customer['bookings'] }} {{ $customer['bookings'] === 1 ? 'reserva' : 'reservas' }}
                                @if ($customer['last']) · última salida {{ $customer['last'] }} @endif
                                @if ($customer['email']) · {{ $customer['email'] }} @endif
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
