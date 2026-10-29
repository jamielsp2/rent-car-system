@php
    $image = \App\Support\SiteContent::hero()['image'];
    $logo = \App\Support\SiteContent::logo();
    $siteName = \App\Support\SiteContent::name();
    // Each panel (admin, aliados) passes its own wording; the admin defaults apply otherwise.
    $panelName ??= 'Panel de gestión';
    $headline ??= 'Tu flota, tus reservas y tu negocio';
    $highlight ??= 'en un solo lugar.';
    $features ??= [
        ['icon' => 'heroicon-o-calendar-days', 'title' => 'Reservas al día', 'text' => 'Confirma, entrega y recibe desde un solo lugar.'],
        ['icon' => 'heroicon-o-document-check', 'title' => 'Contratos digitales', 'text' => 'Firma en tableta, con fotos y daños marcados.'],
        ['icon' => 'heroicon-o-presentation-chart-line', 'title' => 'Informes del negocio', 'text' => 'Ingresos, utilidad y ocupación de cada vehículo.'],
    ];
@endphp
{{-- Left half of the sign-in screen (large screens); the form sits on the right. --}}
<aside class="vd-login-showcase relative isolate hidden overflow-hidden bg-slate-950 text-white lg:flex lg:flex-col lg:justify-between">
    @if ($image)
        <img src="{{ $image }}" alt="" class="vd-login-photo absolute inset-0 -z-20 size-full object-cover opacity-50">
    @endif
    <div class="absolute inset-0 -z-10 bg-gradient-to-br from-slate-950/95 via-slate-900/70 to-[#206bc4]/50"></div>
    <div class="pointer-events-none absolute -bottom-40 -left-32 -z-10 size-[28rem] rounded-full bg-sky-400/25 blur-3xl"></div>
    <div class="pointer-events-none absolute -top-32 right-0 -z-10 size-96 rounded-full bg-[#206bc4]/30 blur-3xl"></div>

    <div class="flex items-center gap-3 p-10 xl:p-14">
        @if ($logo)
            <span class="flex size-12 items-center justify-center rounded-xl bg-white p-1.5 shadow-lg">
                <img src="{{ $logo }}" alt="{{ $siteName }}" class="max-h-full max-w-full">
            </span>
        @endif
        <div class="leading-tight">
            <p class="text-lg font-bold">{{ $siteName }}</p>
            <p class="text-xs tracking-[0.2em] text-white/60 uppercase">{{ $panelName }}</p>
        </div>
    </div>

    <div class="px-10 xl:px-14">
        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold ring-1 ring-white/20 backdrop-blur">
            <span class="size-1.5 animate-pulse rounded-full bg-emerald-400"></span> Panel en línea
        </span>
        <h1 class="mt-5 max-w-xl text-4xl leading-tight font-extrabold tracking-tight xl:text-5xl">
            {{ $headline }} <span class="bg-gradient-to-r from-white to-sky-300 bg-clip-text text-transparent">{{ $highlight }}</span>
        </h1>

        <ul class="mt-10 grid max-w-xl gap-3">
            @foreach ($features as $i => $feature)
                <li class="vd-login-feature flex items-start gap-4 rounded-2xl bg-white/[0.07] p-4 ring-1 ring-white/10 backdrop-blur-md" style="animation-delay: {{ 150 + $i * 120 }}ms">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#206bc4] to-sky-500 shadow-lg shadow-sky-900/40">
                        <x-filament::icon :icon="$feature['icon']" class="size-5 text-white" />
                    </span>
                    <span>
                        <span class="block font-semibold">{{ $feature['title'] }}</span>
                        <span class="block text-sm text-white/65">{{ $feature['text'] }}</span>
                    </span>
                </li>
            @endforeach
        </ul>
    </div>

    <p class="p-10 text-xs text-white/40 xl:p-14">&copy; {{ now()->year }} {{ $siteName }}</p>
</aside>
