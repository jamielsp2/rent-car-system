<x-filament-panels::page>
    {{-- Day header --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-[#206bc4] p-6 text-white shadow-lg shadow-slate-900/10">
        <div class="pointer-events-none absolute -right-16 -top-16 size-64 rounded-full bg-white/10 blur-3xl"></div>
        <svg class="pointer-events-none absolute -bottom-6 right-6 hidden h-36 text-white/5 lg:block" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M3.375 4.5C2.339 4.5 1.5 5.34 1.5 6.375V13.5h12V6.375c0-1.036-.84-1.875-1.875-1.875h-8.25ZM13.5 15h-12v2.625c0 1.035.84 1.875 1.875 1.875h.375a3 3 0 1 1 6 0h3a.75.75 0 0 0 .75-.75V15Z" />
            <path d="M8.25 19.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0ZM15.75 6.75a.75.75 0 0 0-.75.75v11.25c0 .087.015.17.042.248a3 3 0 0 1 5.958.464c.853-.175 1.522-.935 1.464-1.883a18.659 18.659 0 0 0-3.732-10.104 1.837 1.837 0 0 0-1.47-.725H15.75Z" />
            <path d="M19.5 19.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0Z" />
        </svg>

        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    @if ($isToday)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/20 px-2.5 py-0.5 text-xs font-semibold text-emerald-200">
                            <span class="size-1.5 rounded-full bg-emerald-300"></span> Hoy
                        </span>
                    @else
                        <span class="rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-semibold text-white/80">{{ $day->isPast() ? 'Día pasado' : 'Día futuro' }}</span>
                    @endif
                </div>
                <p class="mt-2 text-4xl font-bold tracking-tight">{{ ucfirst($day->translatedFormat('l')) }}</p>
                <p class="text-lg text-white/75">{{ $day->translatedFormat('j \d\e F, Y') }}</p>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="shiftDay(-1)" title="Día anterior" class="flex size-9 items-center justify-center rounded-lg bg-white/10 text-white transition hover:bg-white/20">
                        <x-filament::icon icon="heroicon-m-chevron-left" class="size-5" />
                    </button>
                    <input type="date" wire:model.live="date" class="h-9 rounded-lg border-0 bg-white/10 px-3 text-sm text-white [color-scheme:dark] focus:ring-2 focus:ring-white/40">
                    <button type="button" wire:click="shiftDay(1)" title="Día siguiente" class="flex size-9 items-center justify-center rounded-lg bg-white/10 text-white transition hover:bg-white/20">
                        <x-filament::icon icon="heroicon-m-chevron-right" class="size-5" />
                    </button>
                    @unless ($isToday)
                        <button type="button" wire:click="$set('date', '{{ $today }}')" class="h-9 rounded-lg bg-white px-3 text-sm font-semibold text-slate-900 transition hover:bg-white/90">Volver a hoy</button>
                    @endunless
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3 lg:w-[30rem]">
                @foreach ([
                    ['label' => 'Salidas', 'done' => $deliveriesDone, 'total' => $deliveries->count(), 'icon' => 'heroicon-o-arrow-up-right', 'bar' => 'bg-sky-300'],
                    ['label' => 'Regresos', 'done' => $returnsDone, 'total' => $returns->count(), 'icon' => 'heroicon-o-arrow-down-left', 'bar' => 'bg-amber-300'],
                ] as $stat)
                    <div class="rounded-xl bg-white/10 p-4 backdrop-blur">
                        <p class="flex items-center gap-1.5 text-xs font-medium text-white/70">
                            <x-filament::icon :icon="$stat['icon']" class="size-4" /> {{ $stat['label'] }}
                        </p>
                        <p class="mt-1 text-2xl font-bold tabular-nums">{{ $stat['done'] }}<span class="text-base font-medium text-white/50">/{{ $stat['total'] }}</span></p>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/15">
                            <div class="h-full rounded-full {{ $stat['bar'] }}" style="width: {{ $stat['total'] ? $stat['done'] / $stat['total'] * 100 : 0 }}%"></div>
                        </div>
                        <p class="mt-1 text-[11px] text-white/60">{{ $stat['total'] ? ($stat['done'] === $stat['total'] ? 'Todo listo' : ($stat['total'] - $stat['done']) . ' por hacer') : 'Ninguna' }}</p>
                    </div>
                @endforeach
                <div @class(['rounded-xl p-4 backdrop-blur', 'bg-rose-500/30 ring-1 ring-rose-300/40' => $overdue->isNotEmpty(), 'bg-white/10' => $overdue->isEmpty()])>
                    <p class="flex items-center gap-1.5 text-xs font-medium text-white/70">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="size-4" /> Atrasados
                    </p>
                    <p class="mt-1 text-2xl font-bold tabular-nums">{{ $overdue->count() }}</p>
                    <p class="mt-3.5 text-[11px] text-white/60">{{ $overdue->isEmpty() ? 'Todos a tiempo' : 'Sin devolver' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Overdue returns --}}
    @if ($overdue->isNotEmpty())
        <div class="overflow-hidden rounded-xl border border-rose-200 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-rose-100 bg-rose-50 px-5 py-3">
                <span class="flex size-8 items-center justify-center rounded-lg bg-rose-500 text-white">
                    <x-filament::icon icon="heroicon-m-exclamation-triangle" class="size-4" />
                </span>
                <div>
                    <p class="text-sm font-semibold text-rose-900">Devoluciones atrasadas ({{ $overdue->count() }})</p>
                    <p class="text-xs text-rose-700">Vehículos que ya debieron volver y siguen con el cliente.</p>
                </div>
            </div>
            <ul class="divide-y divide-gray-100 px-5">
                @foreach ($overdue as $booking)
                    @include('filament.bookings.handover-row', ['booking' => $booking, 'kind' => 'overdue'])
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Pickups and returns of the day --}}
    <div class="grid gap-6 xl:grid-cols-2">
        @foreach ([
            ['kind' => 'delivery', 'title' => 'Entregar', 'subtitle' => 'Vehículos que salen ' . ($isToday ? 'hoy' : 'este día'), 'items' => $deliveries, 'done' => $deliveriesDone, 'icon' => 'heroicon-o-arrow-up-right', 'chip' => 'bg-sky-100 text-sky-700', 'bar' => 'bg-sky-500', 'empty' => 'No hay salidas programadas', 'emptyIcon' => 'heroicon-o-truck'],
            ['kind' => 'return', 'title' => 'Recibir', 'subtitle' => 'Vehículos que vuelven ' . ($isToday ? 'hoy' : 'este día'), 'items' => $returns, 'done' => $returnsDone, 'icon' => 'heroicon-o-arrow-down-left', 'chip' => 'bg-amber-100 text-amber-700', 'bar' => 'bg-amber-500', 'empty' => 'No hay regresos programados', 'emptyIcon' => 'heroicon-o-home'],
        ] as $column)
            <div class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="flex size-10 items-center justify-center rounded-lg {{ $column['chip'] }}">
                                <x-filament::icon :icon="$column['icon']" class="size-5" />
                            </span>
                            <div>
                                <h2 class="text-base font-semibold text-gray-950">{{ $column['title'] }} <span class="text-gray-400">({{ $column['items']->count() }})</span></h2>
                                <p class="text-xs text-gray-500">{{ $column['subtitle'] }}</p>
                            </div>
                        </div>
                        @if ($column['items']->isNotEmpty())
                            <span class="text-xs font-medium tabular-nums text-gray-500">{{ $column['done'] }} de {{ $column['items']->count() }} listas</span>
                        @endif
                    </div>
                    @if ($column['items']->isNotEmpty())
                        <div class="mt-3 h-1 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full {{ $column['bar'] }} transition-all" style="width: {{ $column['done'] / $column['items']->count() * 100 }}%"></div>
                        </div>
                    @endif
                </div>

                @if ($column['items']->isEmpty())
                    <div class="flex flex-1 flex-col items-center justify-center px-6 py-12 text-center">
                        <span class="flex size-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <x-filament::icon :icon="$column['emptyIcon']" class="size-7" />
                        </span>
                        <p class="mt-3 text-sm font-medium text-gray-700">{{ $column['empty'] }}</p>
                        <p class="text-xs text-gray-400">Usa las flechas de arriba para ver otro día.</p>
                    </div>
                @else
                    @php
                        $isDelivery = $column['kind'] === 'delivery';
                        $isDone = fn ($b) => $isDelivery ? \App\Filament\Clusters\Reservas\Pages\TodayHandovers::delivered($b) : \App\Filament\Clusters\Reservas\Pages\TodayHandovers::returned($b);
                        $groups = [
                            ['label' => $isDelivery ? 'Por salir' : 'Por recibir', 'dot' => 'bg-amber-400', 'items' => $column['items']->reject($isDone)->values()],
                            ['label' => $isDelivery ? 'Ya salieron' : 'Ya regresaron', 'dot' => 'bg-emerald-500', 'items' => $column['items']->filter($isDone)->values()],
                        ];
                    @endphp
                    @foreach ($groups as $group)
                        @if ($group['items']->isNotEmpty())
                            <div class="flex items-center gap-2 border-b border-gray-100 bg-gray-50 px-5 py-2 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                <span class="size-2 rounded-full {{ $group['dot'] }}"></span>
                                {{ $group['label'] }} <span class="text-gray-400">({{ $group['items']->count() }})</span>
                            </div>
                            <ul class="px-5">
                                @foreach ($group['items'] as $booking)
                                    @include('filament.bookings.handover-row', ['booking' => $booking, 'kind' => $column['kind'], 'last' => $loop->last])
                                @endforeach
                            </ul>
                        @endif
                    @endforeach
                @endif
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
