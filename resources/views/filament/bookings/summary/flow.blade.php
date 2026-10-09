<x-filament-widgets::widget>
    <x-filament::section class="h-full">
        <x-slot name="heading">Estado de las reservas</x-slot>
        <x-slot name="description">{{ $total }} {{ $total === 1 ? 'reserva' : 'reservas' }} en el período</x-slot>

        @if (! $total)
            <p class="py-8 text-center text-sm text-gray-500">Sin reservas en este período.</p>
        @else
            {{-- Whole period as one segmented bar --}}
            <div class="flex h-3 overflow-hidden rounded-full bg-slate-100">
                @foreach ($steps as $step)
                    @if ($step['count'])
                        <div class="h-full first:rounded-l-full last:rounded-r-full" style="width: {{ $step['share'] }}%; background-color: {{ $step['color'] }}" title="{{ $step['label'] }}: {{ $step['count'] }}"></div>
                    @endif
                @endforeach
            </div>

            <ul class="mt-5 space-y-1">
                @foreach ($steps as $step)
                    <li>
                        <a href="{{ $step['url'] }}" class="flex items-center gap-3 rounded-lg px-2 py-2 transition hover:bg-slate-50">
                            <span class="size-2.5 shrink-0 rounded-full" style="background-color: {{ $step['color'] }}"></span>
                            <span class="flex-1 truncate text-sm text-gray-700">{{ $step['label'] }}</span>
                            <span class="text-xs tabular-nums text-gray-400">{{ round($step['share']) }}%</span>
                            <span class="w-8 text-right text-sm font-semibold tabular-nums text-gray-950">{{ $step['count'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 flex items-center justify-between rounded-lg bg-gradient-to-r from-emerald-50 to-teal-50 px-4 py-3">
                <div>
                    <p class="text-xs font-medium text-emerald-800">Se concretaron</p>
                    <p class="text-xs text-emerald-700/70">Confirmadas, entregadas o completadas</p>
                </div>
                <p class="text-2xl font-bold tabular-nums text-emerald-700">{{ $conversion }}%</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
