<x-filament-widgets::widget>
    <div class="space-y-4">
        <p class="text-sm text-gray-500">
            <span class="font-semibold text-gray-900">{{ $range }}</span>
            · comparado con {{ $previous }}
        </p>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {{-- Featured: rental income --}}
            @php($card = $hero)
            <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-slate-900 via-slate-800 to-[#206bc4] p-5 text-white shadow-lg shadow-slate-900/10" style="animation: vd-rise .6s cubic-bezier(.22,1,.36,1) both">
                <div class="pointer-events-none absolute -right-10 -top-10 size-40 rounded-full bg-white/10 blur-2xl"></div>
                <div class="relative flex items-start justify-between gap-3">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-white/15">
                        <x-filament::icon icon="heroicon-o-banknotes" class="size-5 text-white" />
                    </div>
                    @include('filament.reports.partials.delta', ['delta' => $card['delta'], 'dark' => true])
                </div>
                <p class="relative mt-4 text-xs font-medium uppercase tracking-wider text-white/70">{{ $card['label'] }}</p>
                <p class="relative mt-1 text-3xl font-bold tracking-tight tabular-nums">{{ $card['value'] }}</p>
                <p class="relative mt-1 text-xs text-white/70">{{ $card['hint'] }}</p>
                @if ($card['spark'])
                    <svg viewBox="0 0 100 32" preserveAspectRatio="none" class="relative mt-3 h-10 w-full" aria-hidden="true">
                        <defs>
                            <linearGradient id="spark-ally-income" x1="0" x2="0" y1="0" y2="1">
                                <stop offset="0%" stop-color="#fff" stop-opacity="0.35" />
                                <stop offset="100%" stop-color="#fff" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                        <path d="{{ $card['spark']['area'] }}" fill="url(#spark-ally-income)" />
                        <path d="{{ $card['spark']['line'] }}" fill="none" stroke="#fff" stroke-width="1.5" vector-effect="non-scaling-stroke" stroke-linejoin="round" />
                    </svg>
                @endif
            </div>

            {{-- Profitability --}}
            @php($card = $profit)
            <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm" style="animation: vd-rise .6s .12s cubic-bezier(.22,1,.36,1) both">
                <div class="flex items-start justify-between gap-3">
                    <div @class([
                        'flex size-10 items-center justify-center rounded-lg',
                        'bg-emerald-50 text-emerald-600' => ! $card['negative'],
                        'bg-rose-50 text-rose-600' => $card['negative'],
                    ])>
                        <x-filament::icon icon="heroicon-o-chart-bar-square" class="size-5" />
                    </div>
                    @include('filament.reports.partials.delta', ['delta' => $card['delta']])
                </div>
                <p class="mt-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ $card['label'] }}</p>
                <p @class(['mt-1 text-3xl font-bold tracking-tight tabular-nums', 'text-rose-600' => $card['negative'], 'text-gray-950' => ! $card['negative']])>{{ $card['value'] }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ $card['hint'] }}</p>
            </div>

            {{-- Occupancy ring --}}
            @php($pct = min(100, max(0, $occupancy['value'])))
            @php($ringColor = $pct >= 60 ? '#10b981' : ($pct >= 30 ? '#f59e0b' : '#f43f5e'))
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm" style="animation: vd-rise .6s .24s cubic-bezier(.22,1,.36,1) both">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                        <x-filament::icon icon="heroicon-o-key" class="size-5" />
                    </div>
                    @include('filament.reports.partials.delta', ['delta' => $occupancy['delta']])
                </div>
                <p class="mt-4 text-xs font-medium uppercase tracking-wider text-gray-500">Ocupación de tus vehículos</p>
                <div class="mt-2 flex items-center gap-4">
                    <div class="relative size-24 shrink-0">
                        <svg viewBox="0 0 100 100" class="size-24 -rotate-90" aria-hidden="true">
                            <circle cx="50" cy="50" r="42" fill="none" stroke="#f1f5f9" stroke-width="10" />
                            <circle cx="50" cy="50" r="42" fill="none" stroke="{{ $ringColor }}" stroke-width="10" stroke-linecap="round"
                                stroke-dasharray="263.9" stroke-dashoffset="{{ round(263.9 * (1 - $pct / 100), 1) }}" />
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center text-xl font-bold tabular-nums text-gray-950">{{ $occupancy['value'] }}%</span>
                    </div>
                    <p class="text-xs leading-relaxed text-gray-500">
                        <span class="block text-sm font-semibold text-gray-900">{{ $occupancy['rented'] }} de {{ $occupancy['total'] }}</span>
                        rentados ahora. La ocupación son los días rentados sobre los días del período.
                    </p>
                </div>
            </div>

            {{-- Quick status --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm" style="animation: vd-rise .6s .36s cubic-bezier(.22,1,.36,1) both">
                <div class="flex size-10 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="size-5" />
                </div>
                <p class="mt-4 text-xs font-medium uppercase tracking-wider text-gray-500">Vehículos rentados ahora</p>
                <p class="mt-1 text-3xl font-bold tracking-tight tabular-nums text-gray-950">{{ $occupancy['rented'] }}<span class="text-lg font-semibold text-gray-400"> / {{ $occupancy['total'] }}</span></p>
                <p class="mt-1 text-xs text-gray-500">Con renta en curso o reservada hoy</p>
            </div>
        </div>

        {{-- Secondary metrics --}}
        <div class="grid gap-px overflow-hidden rounded-xl border border-gray-200 bg-gray-200 shadow-sm sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($metrics as $metric)
                <div class="flex gap-3 bg-white p-4">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                        <x-filament::icon :icon="$metric['icon']" class="size-[18px]" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-xs font-medium text-gray-500">{{ $metric['label'] }}</p>
                            @include('filament.reports.partials.delta', ['delta' => $metric['delta']])
                        </div>
                        <p class="mt-0.5 text-xl font-bold tabular-nums text-gray-950">{{ $metric['value'] }}</p>
                        <p class="truncate text-xs text-gray-400">{{ $metric['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
