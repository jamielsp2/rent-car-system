<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Tus vehículos</x-slot>
        <x-slot name="description">Renta sin ITBIS ni servicios repartida por los días dentro del período, menos los gastos de cada vehículo.</x-slot>
        <x-slot name="afterHeader">
            <div class="text-right">
                <p class="text-xs text-gray-500">Rentabilidad total</p>
                <p class="text-lg font-bold tabular-nums text-gray-950">{{ $total }}</p>
            </div>
        </x-slot>

        @if ($rows->isEmpty())
            <p class="py-8 text-center text-sm text-gray-500">Todavía no tienes vehículos asignados. La empresa los asigna desde su panel.</p>
        @else
            <div class="-mx-6 -mt-6 overflow-x-auto">
                <table class="w-full min-w-[980px] text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-slate-50 text-left text-[11px] font-medium uppercase tracking-wider text-gray-500">
                            <th class="w-10 px-6 py-3">#</th>
                            <th class="py-3 pr-4">Vehículo</th>
                            <th class="py-3 pr-4">Precio por día</th>
                            <th class="py-3 pr-4 text-center">Renta mínima</th>
                            <th class="py-3 pr-4">Situación</th>
                            <th class="py-3 pr-4">Quién la rentó</th>
                            <th class="w-56 py-3 pr-4">Ingresos</th>
                            <th class="w-44 py-3 pr-4">Ocupación</th>
                            <th class="py-3 pr-4 text-right">Gastos</th>
                            <th class="py-3 pr-6 text-right">Rentabilidad</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($rows as $i => $row)
                            <tr class="transition hover:bg-slate-50/70" style="animation: vd-rise .5s {{ min(600, $i * 80) }}ms cubic-bezier(.22,1,.36,1) both">
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
                                    <div class="flex items-center gap-3">
                                        @if ($row['image'])
                                            <img src="{{ $row['image'] }}" alt="" loading="lazy" class="h-10 w-14 shrink-0 rounded-md object-cover ring-1 ring-gray-950/5">
                                        @else
                                            <span class="h-10 w-14 shrink-0 rounded-md bg-slate-100"></span>
                                        @endif
                                        <span class="min-w-0">
                                            <span class="block truncate font-medium text-gray-950">{{ $row['name'] }}</span>
                                            <span class="block truncate text-xs text-gray-500">{{ $row['meta'] ?: '—' }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 pr-4 font-medium tabular-nums text-gray-950">{{ $row['price'] }}</td>
                                <td class="py-3 pr-4 text-center tabular-nums text-gray-700">{{ $row['min_days'] }} {{ $row['min_days'] === 1 ? 'día' : 'días' }}</td>
                                <td class="py-3 pr-4">
                                    <span @class([
                                        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold',
                                        'bg-emerald-50 text-emerald-700' => $row['status_color'] === 'success',
                                        'bg-amber-50 text-amber-700' => $row['status_color'] === 'warning',
                                        'bg-rose-50 text-rose-700' => $row['status_color'] === 'danger',
                                    ])>
                                        <span @class([
                                            'size-1.5 rounded-full',
                                            'bg-emerald-500' => $row['status_color'] === 'success',
                                            'bg-amber-500 animate-pulse' => $row['status_color'] === 'warning',
                                            'bg-rose-500 animate-pulse' => $row['status_color'] === 'danger',
                                        ])></span>
                                        {{ $row['status'] }}
                                    </span>
                                </td>
                                <td class="py-3 pr-4">
                                    @if ($row['renter'])
                                        <span class="block font-medium text-gray-900">{{ $row['renter'] }}</span>
                                        <span class="block text-xs text-gray-500">{{ $row['dates'] }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
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
                                    <span class="text-xs text-gray-400">{{ $row['rentals'] }} {{ $row['rentals'] === 1 ? 'renta' : 'rentas' }}</span>
                                </td>
                                <td @class(['py-3 pr-4 text-right tabular-nums', 'text-rose-600' => $row['expenses'] !== '—', 'text-gray-300' => $row['expenses'] === '—'])>{{ $row['expenses'] }}</td>
                                <td @class([
                                    'py-3 pr-6 text-right font-semibold tabular-nums',
                                    'text-emerald-600' => $row['profit_value'] > 0,
                                    'text-rose-600' => $row['profit_value'] < 0,
                                    'text-gray-400' => $row['profit_value'] == 0,
                                ])>{{ $row['profit'] }}</td>
                            </tr>
                            <tr>
                                <td colspan="10" class="px-6 pb-3 pt-0">
                                    <details class="group rounded-lg bg-slate-50/70 ring-1 ring-gray-950/5">
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-3 py-2 text-xs font-medium text-gray-600 hover:text-primary-600">
                                            <span>Historial de reservas ({{ count($row['history']) }})</span>
                                            <x-filament::icon icon="heroicon-m-chevron-down" class="size-4 transition group-open:rotate-180" />
                                        </summary>
                                        @if (empty($row['history']))
                                            <p class="px-3 pb-3 text-xs text-gray-500">Este vehículo todavía no tiene reservas.</p>
                                        @else
                                            <div class="overflow-x-auto px-3 pb-3">
                                                <table class="w-full min-w-[640px] text-xs">
                                                    <thead>
                                                        <tr class="text-left text-[11px] uppercase tracking-wider text-gray-400">
                                                            <th class="py-2 pr-4 font-medium">Reserva</th>
                                                            <th class="py-2 pr-4 font-medium">Quién la rentó</th>
                                                            <th class="py-2 pr-4 font-medium">Desde</th>
                                                            <th class="py-2 pr-4 font-medium">Hasta</th>
                                                            <th class="py-2 pr-4 text-right font-medium">Precio</th>
                                                            <th class="py-2 font-medium">Estado</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100">
                                                        @foreach ($row['history'] as $rental)
                                                            <tr>
                                                                <td class="py-2 pr-4 font-medium text-gray-700">{{ $rental['booking'] }}</td>
                                                                <td class="py-2 pr-4 text-gray-900">{{ $rental['customer'] }}</td>
                                                                <td class="py-2 pr-4 tabular-nums text-gray-600">{{ $rental['from'] }}</td>
                                                                <td class="py-2 pr-4 tabular-nums text-gray-600">{{ $rental['to'] }}</td>
                                                                <td class="py-2 pr-4 text-right tabular-nums text-gray-900">{{ $rental['price'] }}</td>
                                                                <td class="py-2 text-gray-600">{{ $rental['status'] }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
