<x-filament-widgets::widget>
    <div class="grid h-full gap-4 md:grid-cols-2">
        @foreach ([
            ['title' => 'Servicios adicionales', 'subtitle' => 'Extras vendidos con las rentas', 'rows' => $services, 'total' => $servicesTotal, 'icon' => 'heroicon-o-sparkles', 'bar' => 'bg-violet-500', 'chip' => 'bg-violet-50 text-violet-600', 'empty' => 'No se vendieron extras en este período.'],
            ['title' => 'Gastos por concepto', 'subtitle' => 'En qué se fue el dinero de la flota', 'rows' => $expenses, 'total' => $expensesTotal, 'icon' => 'heroicon-o-wrench-screwdriver', 'bar' => 'bg-rose-400', 'chip' => 'bg-rose-50 text-rose-600', 'empty' => 'No hay gastos registrados en este período.'],
        ] as $block)
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex size-9 items-center justify-center rounded-lg {{ $block['chip'] }}">
                            <x-filament::icon :icon="$block['icon']" class="size-[18px]" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-gray-950">{{ $block['title'] }}</p>
                            <p class="text-xs text-gray-500">{{ $block['subtitle'] }}</p>
                        </div>
                    </div>
                    <p class="text-lg font-bold tabular-nums text-gray-950">{{ $block['total'] }}</p>
                </div>

                @if ($block['rows']->isEmpty())
                    <p class="py-8 text-center text-sm text-gray-500">{{ $block['empty'] }}</p>
                @else
                    <ul class="mt-5 space-y-3">
                        @foreach ($block['rows'] as $row)
                            <li>
                                <div class="flex items-baseline justify-between gap-3 text-sm">
                                    <span class="truncate text-gray-700">
                                        {{ $row['name'] }}
                                        @isset($row['count'])<span class="text-xs text-gray-400">× {{ $row['count'] }}</span>@endisset
                                    </span>
                                    <span class="shrink-0 font-medium tabular-nums text-gray-950">{{ $row['amount'] }}</span>
                                </div>
                                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full {{ $block['bar'] }}" style="width: {{ $row['share'] }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>
