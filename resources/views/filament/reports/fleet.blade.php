<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Rendimiento de la flota</x-slot>
        <x-slot name="description">Renta sin ITBIS ni servicios repartida por los días dentro del período, menos los gastos de cada vehículo.</x-slot>
        <x-slot name="afterHeader">
            <div class="text-right">
                <p class="text-xs text-gray-500">Utilidad de la flota</p>
                <p class="text-lg font-bold tabular-nums text-gray-950">{{ $total }}</p>
            </div>
        </x-slot>

        @if ($rows->isEmpty())
            <p class="py-8 text-center text-sm text-gray-500">Ningún vehículo se rentó en este período.</p>
        @else
            <div class="-mx-6 -mt-6 overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-slate-50 text-left text-[11px] font-medium uppercase tracking-wider text-gray-500">
                            <th class="w-10 px-6 py-3">#</th>
                            <th class="py-3 pr-4">Vehículo</th>
                            <th class="w-56 py-3 pr-4">Ingresos</th>
                            <th class="w-44 py-3 pr-4">Ocupación</th>
                            <th class="py-3 pr-4 text-center">Rentas</th>
                            <th class="py-3 pr-4 text-right">Gastos</th>
                            <th class="py-3 pr-6 text-right">Utilidad</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($rows as $i => $row)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-6 py-3">
                                    <span @class([
                                        'flex size-7 items-center justify-center rounded-full text-xs font-bold',
                                        'bg-amber-100 text-amber-700' => $i === 0,
                                        'bg-slate-200 text-slate-700' => $i === 1,
                                        'bg-orange-100 text-orange-700' => $i === 2,
                                        'text-gray-400' => $i > 2,
                                    ])>{{ $i + 1 }}</span>
                                </td>
                                <td class="py-3 pr-4">
                                    <a href="{{ $row['url'] }}" class="group flex items-center gap-3">
                                        @if ($row['image'])
                                            <img src="{{ $row['image'] }}" alt="" loading="lazy" class="h-10 w-14 shrink-0 rounded-md object-cover ring-1 ring-gray-950/5">
                                        @else
                                            <span class="h-10 w-14 shrink-0 rounded-md bg-slate-100"></span>
                                        @endif
                                        <span class="min-w-0">
                                            <span class="block truncate font-medium text-gray-950 group-hover:text-primary-600">{{ $row['name'] }}</span>
                                            <span class="block truncate text-xs text-gray-500">{{ $row['meta'] ?: '—' }}</span>
                                        </span>
                                    </a>
                                </td>
                                <td class="py-3 pr-4">
                                    <div class="flex items-center gap-3">
                                        <span class="w-20 shrink-0 font-medium tabular-nums text-gray-950">{{ $row['income'] }}</span>
                                        <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                                            <span class="block h-full rounded-full bg-[#206bc4]" style="width: {{ $row['income_share'] }}%"></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 pr-4">
                                    <div class="flex items-center gap-3">
                                        <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                                            <span @class([
                                                'block h-full rounded-full',
                                                'bg-emerald-500' => $row['utilization'] >= 60,
                                                'bg-amber-400' => $row['utilization'] >= 30 && $row['utilization'] < 60,
                                                'bg-rose-400' => $row['utilization'] < 30,
                                            ]) style="width: {{ $row['utilization'] }}%"></span>
                                        </span>
                                        <span class="w-10 text-right text-xs font-medium tabular-nums text-gray-600">{{ $row['utilization'] }}%</span>
                                    </div>
                                    <span class="text-xs text-gray-400">{{ $row['days'] }} días rentado</span>
                                </td>
                                <td class="py-3 pr-4 text-center tabular-nums text-gray-700">{{ $row['rentals'] }}</td>
                                <td @class(['py-3 pr-4 text-right tabular-nums', 'text-rose-600' => $row['expenses'] !== '—', 'text-gray-300' => $row['expenses'] === '—'])>{{ $row['expenses'] }}</td>
                                <td @class([
                                    'py-3 pr-6 text-right font-semibold tabular-nums',
                                    'text-emerald-600' => $row['profit_value'] > 0,
                                    'text-rose-600' => $row['profit_value'] < 0,
                                    'text-gray-400' => $row['profit_value'] == 0,
                                ])>{{ $row['profit'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @endif

        @if ($idle->isNotEmpty())
            <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50/60 p-4">
                <p class="flex items-center gap-2 text-sm font-medium text-amber-800">
                    <x-filament::icon icon="heroicon-m-exclamation-triangle" class="size-4" />
                    {{ $idle->count() }} {{ $idle->count() === 1 ? 'vehículo no se rentó' : 'vehículos no se rentaron' }} en este período
                </p>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach ($idle as $car)
                        <a href="{{ $car['url'] }}" class="rounded-full bg-white px-2.5 py-1 text-xs text-gray-700 ring-1 ring-amber-200 transition hover:text-primary-600 hover:ring-amber-300">{{ $car['name'] }}</a>
                    @endforeach
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
