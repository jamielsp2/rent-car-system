<x-filament-widgets::widget>
    <div class="flex h-full flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="bg-gradient-to-br from-emerald-500 to-teal-600 p-5 text-white">
            <p class="text-xs font-medium uppercase tracking-wider text-white/80">Próximos 30 días</p>
            <p class="mt-1 text-3xl font-bold tracking-tight tabular-nums">{{ $revenue }}</p>
            <p class="mt-1 text-sm text-white/85">
                {{ $count }} {{ $count === 1 ? 'salida reservada' : 'salidas reservadas' }}
                @if ($pending) · {{ $pending }} por confirmar @endif
            </p>
        </div>

        <div class="flex-1 p-5">
            @if ($next->isEmpty())
                <p class="py-6 text-center text-sm text-gray-500">No hay salidas reservadas.</p>
            @else
                <ul class="space-y-3">
                    @foreach ($next as $item)
                        <li>
                            <a href="{{ $item['url'] }}" class="group flex items-center gap-3">
                                <span class="flex w-11 shrink-0 flex-col items-center rounded-lg border border-gray-200 py-1 leading-none">
                                    <span class="text-[10px] font-semibold uppercase text-rose-600">{{ $item['month'] }}</span>
                                    <span class="mt-0.5 text-lg font-bold tabular-nums text-gray-950">{{ $item['day'] }}</span>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-gray-950 group-hover:text-primary-600">{{ $item['car'] }}</span>
                                    <span class="block truncate text-xs text-gray-500">
                                        {{ $item['customer'] }}
                                        @if ($item['pending']) · <span class="text-amber-600">por confirmar</span> @endif
                                    </span>
                                </span>
                                <span class="shrink-0 text-sm font-semibold tabular-nums text-gray-950">{{ $item['amount'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
