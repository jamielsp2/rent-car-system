<x-filament-widgets::widget>
    <div class="space-y-4">
        <p class="text-sm text-gray-500">
            <span class="font-semibold text-gray-900">{{ $range }}</span>
            · comparado con {{ $previous }}
        </p>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {{-- Bookings of the period --}}
            <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-slate-900 via-slate-800 to-[#206bc4] p-5 text-white shadow-lg shadow-slate-900/10">
                <div class="pointer-events-none absolute -right-10 -top-10 size-40 rounded-full bg-white/10 blur-2xl"></div>
                <div class="relative flex items-start justify-between gap-3">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-white/15">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="size-5 text-white" />
                    </div>
                    @include('filament.reports.partials.delta', ['delta' => $bookings['delta'], 'dark' => true])
                </div>
                <p class="relative mt-4 text-xs font-medium uppercase tracking-wider text-white/70">Reservas del período</p>
                <p class="relative mt-1 text-3xl font-bold tracking-tight tabular-nums">{{ $bookings['value'] }}</p>
                <p class="relative mt-1 text-xs text-white/70">{{ $bookings['hint'] }}</p>
                @if ($bookings['spark'])
                    <svg viewBox="0 0 100 32" preserveAspectRatio="none" class="relative mt-3 h-10 w-full" aria-hidden="true">
                        <defs>
                            <linearGradient id="spark-bookings-summary" x1="0" x2="0" y1="0" y2="1">
                                <stop offset="0%" stop-color="#fff" stop-opacity="0.35" />
                                <stop offset="100%" stop-color="#fff" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                        <path d="{{ $bookings['spark']['area'] }}" fill="url(#spark-bookings-summary)" />
                        <path d="{{ $bookings['spark']['line'] }}" fill="none" stroke="#fff" stroke-width="1.5" vector-effect="non-scaling-stroke" stroke-linejoin="round" />
                    </svg>
                @endif
            </div>

            {{-- Waiting for confirmation --}}
            <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div @class(['flex size-10 items-center justify-center rounded-lg', 'bg-amber-50 text-amber-600' => $pending['value'], 'bg-emerald-50 text-emerald-600' => ! $pending['value']])>
                        <x-filament::icon :icon="$pending['value'] ? 'heroicon-o-bell-alert' : 'heroicon-o-check-circle'" class="size-5" />
                    </div>
                    @if ($pending['value'])
                        <span class="relative flex size-2.5">
                            <span class="absolute inline-flex size-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex size-2.5 rounded-full bg-amber-500"></span>
                        </span>
                    @endif
                </div>
                <p class="mt-4 text-xs font-medium uppercase tracking-wider text-gray-500">Por confirmar</p>
                <p class="mt-1 text-3xl font-bold tracking-tight tabular-nums text-gray-950">{{ $pending['value'] }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ $pending['value'] ? 'Reservas esperando tu respuesta' : 'Todo al día' }}</p>
                @if ($pending['value'])
                    <a href="{{ $pending['url'] }}" class="mt-auto inline-flex items-center gap-1 pt-4 text-sm font-semibold text-amber-700 hover:text-amber-800">
                        Revisar pendientes
                        <x-filament::icon icon="heroicon-m-arrow-right" class="size-4" />
                    </a>
                @endif
            </div>

            {{-- With customers right now --}}
            <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                        <x-filament::icon icon="heroicon-o-key" class="size-5" />
                    </div>
                    @if ($rented['overdue'])
                        <span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700">{{ $rented['overdue'] }} {{ $rented['overdue'] === 1 ? 'atrasado' : 'atrasados' }}</span>
                    @endif
                </div>
                <p class="mt-4 text-xs font-medium uppercase tracking-wider text-gray-500">Rentados ahora</p>
                <p class="mt-1 text-3xl font-bold tracking-tight tabular-nums text-gray-950">
                    {{ $rented['value'] }}<span class="text-base font-medium text-gray-400"> / {{ $rented['fleet'] }}</span>
                </p>
                <p class="mt-1 text-xs text-gray-500">Vehículos con el cliente</p>
                <div class="mt-auto pt-4">
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-gradient-to-r from-violet-500 to-fuchsia-500" style="width: {{ $rented['share'] }}%"></div>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-400">{{ round($rented['share']) }}% de la flota en la calle</p>
                </div>
            </div>

            {{-- Next 7 days --}}
            <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex size-10 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                    <x-filament::icon icon="heroicon-o-arrows-right-left" class="size-5" />
                </div>
                <p class="mt-4 text-xs font-medium uppercase tracking-wider text-gray-500">Próximos 7 días</p>
                <div class="mt-1 grid grid-cols-2 divide-x divide-gray-100">
                    <div class="pr-3">
                        <p class="text-3xl font-bold tracking-tight tabular-nums text-gray-950">{{ $next['pickups'] }}</p>
                        <p class="flex items-center gap-1 text-xs text-gray-500"><x-filament::icon icon="heroicon-m-arrow-up-right" class="size-3.5 text-sky-500" /> Salidas</p>
                    </div>
                    <div class="pl-4">
                        <p class="text-3xl font-bold tracking-tight tabular-nums text-gray-950">{{ $next['returns'] }}</p>
                        <p class="flex items-center gap-1 text-xs text-gray-500"><x-filament::icon icon="heroicon-m-arrow-down-left" class="size-3.5 text-amber-500" /> Regresos</p>
                    </div>
                </div>
                <a href="{{ $next['url'] }}" class="mt-auto inline-flex items-center gap-1 pt-4 text-sm font-semibold text-primary-600 hover:text-primary-700">
                    Ver entregas de hoy
                    <x-filament::icon icon="heroicon-m-arrow-right" class="size-4" />
                </a>
            </div>
        </div>

        {{-- Secondary figures --}}
        <div class="grid gap-px overflow-hidden rounded-xl border border-gray-200 bg-gray-200 shadow-sm sm:grid-cols-3 xl:grid-cols-6">
            @foreach ($metrics as $metric)
                <div class="flex items-center gap-3 bg-white p-4">
                    <div @class([
                        'flex size-9 shrink-0 items-center justify-center rounded-lg',
                        'bg-sky-50 text-sky-600' => $metric['tone'] === 'sky',
                        'bg-emerald-50 text-emerald-600' => $metric['tone'] === 'emerald',
                        'bg-rose-50 text-rose-600' => $metric['tone'] === 'rose',
                        'bg-violet-50 text-violet-600' => $metric['tone'] === 'violet',
                        'bg-amber-50 text-amber-600' => $metric['tone'] === 'amber',
                        'bg-slate-100 text-slate-600' => $metric['tone'] === 'slate',
                    ])>
                        <x-filament::icon :icon="$metric['icon']" class="size-[18px]" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-medium text-gray-500">{{ $metric['label'] }}</p>
                        <div class="flex flex-wrap items-baseline gap-x-2">
                            <p class="text-lg font-bold tabular-nums text-gray-950">{{ $metric['value'] }}</p>
                            @include('filament.reports.partials.delta', ['delta' => $metric['delta']])
                        </div>
                        @isset($metric['hint'])<p class="truncate text-xs text-gray-400">{{ $metric['hint'] }}</p>@endisset
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
