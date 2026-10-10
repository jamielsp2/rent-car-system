@php
    $hero = \App\Support\SiteContent::hero();
    $today = \App\Support\LocalTime::today();
    $invitations = config('site.invitations');
    $seo = ['schema' => [\App\Support\Seo::organization()]];
@endphp
<x-site.layout :seo="$seo">
    {{-- Hero with search --}}
    <section class="relative isolate overflow-hidden bg-black">
        @if ($hero['image'])
            <img src="{{ $hero['image'] }}" alt="" class="absolute inset-0 -z-10 size-full object-cover opacity-55">
        @endif
        <div class="absolute inset-0 -z-10 bg-gradient-to-b from-black/75 via-black/55 to-black/75"></div>

        <div class="mx-auto max-w-7xl px-4 pt-24 pb-36 text-center sm:px-6 lg:pt-32">
            <span class="inline-block rounded-lg bg-brand px-4 py-2 text-sm font-bold text-white">{{ $hero['badge'] }}</span>
            <h1 class="mx-auto mt-6 max-w-3xl text-4xl leading-tight font-extrabold text-white sm:text-6xl">{{ $hero['title'] }}</h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-white/85 sm:text-xl">{{ $hero['subtitle'] }}</p>
        </div>
    </section>

    <div class="relative z-10 mx-auto -mt-20 max-w-6xl px-4 sm:px-6">
        <form action="{{ lroute('site.cars') }}" method="GET" class="grid gap-4 rounded-2xl bg-white p-5 shadow-2xl ring-1 ring-black/5 sm:p-6 md:grid-cols-[1fr_1fr_1fr_auto] md:items-end">
            <label class="block">
                <span class="mb-1.5 flex items-center gap-2 text-sm font-semibold"><x-site.icon name="calendar" class="size-4 text-brand" /> {{ __('Fecha de salida') }}</span>
                <input type="date" name="from" min="{{ $today->toDateString() }}" value="{{ $today->addDay()->toDateString() }}" class="w-full rounded-lg border border-black/10 px-3 py-2.5 focus:border-brand focus:ring-brand">
            </label>
            <label class="block">
                <span class="mb-1.5 flex items-center gap-2 text-sm font-semibold"><x-site.icon name="calendar" class="size-4 text-brand" /> {{ __('Fecha de devolución') }}</span>
                <input type="date" name="to" min="{{ $today->addDay()->toDateString() }}" value="{{ $today->addDays(3)->toDateString() }}" class="w-full rounded-lg border border-black/10 px-3 py-2.5 focus:border-brand focus:ring-brand">
            </label>
            <label class="block">
                <span class="mb-1.5 block text-sm font-semibold">{{ __('Tipo de vehículo') }}</span>
                <select name="type[]" class="w-full rounded-lg border border-black/10 px-3 py-2.5 focus:border-brand focus:ring-brand">
                    <option value="">{{ __('Todos') }}</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="flex items-center justify-center gap-2 rounded-lg bg-brand px-7 py-3 font-bold text-white transition hover:bg-brand-dark">
                <x-site.icon name="search" class="size-5" /> {{ __('Buscar vehículo') }}
            </button>
        </form>
    </div>

    {{-- Browse by type --}}
    @if ($types->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pt-24 sm:px-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-extrabold sm:text-4xl">{{ __('Buscar por tipo') }}</h2>
                    <p class="mt-2 text-neutral-600">{{ __('Encuentra el vehículo perfecto para cada ocasión') }}</p>
                </div>
                <a href="{{ lroute('site.cars') }}" class="flex items-center gap-2 font-bold hover:text-brand">{{ __('Ver todos') }} <x-site.icon name="arrow-right" class="size-4" /></a>
            </div>
            <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($types as $type)
                    <a href="{{ lroute('site.cars', ['type' => [$type->id]]) }}" class="rounded-2xl border border-black/10 p-5 text-center transition hover:border-brand hover:shadow-lg">
                        <p class="text-lg font-bold">{{ $type->name }}</p>
                        <p class="mt-1 text-sm text-neutral-500">{{ $type->cars_count }} {{ $type->cars_count === 1 ? __('vehículo') : __('vehículos') }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- About --}}
    <x-site.about class="pt-24" />

    {{-- Popular vehicles --}}
    <section class="mx-auto max-w-7xl px-4 pt-24 sm:px-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-3xl font-extrabold sm:text-4xl">{{ __('Vehículos populares') }}</h2>
                <p class="mt-2 text-neutral-600">{{ __('Los favoritos de nuestros clientes') }}</p>
            </div>
            <a href="{{ lroute('site.cars') }}" class="flex items-center gap-2 font-bold hover:text-brand">{{ __('Ver más') }} <x-site.icon name="arrow-right" class="size-4" /></a>
        </div>
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($popular as $car)
                <x-site.car-card :car="$car" />
            @endforeach
        </div>
    </section>

    {{-- Invitations --}}
    <section class="mt-24 bg-neutral-100">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6">
            <div class="mx-auto max-w-3xl text-center">
                <h2 class="text-3xl font-extrabold sm:text-4xl">{{ __($invitations['title']) }}</h2>
                <p class="mt-4 text-lg text-neutral-600">{{ __($invitations['text']) }}</p>
            </div>
            <div class="mt-12 grid gap-6 md:grid-cols-2">
                @foreach ($invitations['items'] as $item)
                    <div @class(['rounded-2xl p-8', 'bg-white' => $loop->first, 'bg-black text-white' => ! $loop->first])>
                        <h3 class="text-2xl font-bold">{{ __($item['title']) }}</h3>
                        <p @class(['mt-3 leading-relaxed', 'text-neutral-600' => $loop->first, 'text-white/75' => ! $loop->first])>{{ __($item['text']) }}</p>
                        <a href="{{ lroute('site.cars') }}" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-brand px-5 py-3 font-bold text-white transition hover:bg-brand-dark">
                            {{ __($item['button']) }} <x-site.icon name="arrow-right" class="size-4" />
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Testimonials --}}
    <x-site.testimonials :testimonials="$testimonials" />
</x-site.layout>
