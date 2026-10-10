@php
    $seo = ['schema' => [\App\Support\Seo::organization(), ...\App\Support\Seo::trail(__('Sobre nosotros'), 'site.about')]];
@endphp
<x-site.layout title="{{ __('Sobre nosotros') }}" :description="\App\Support\SiteContent::about()['text']" :seo="$seo">
    <x-site.page-header
        title="{{ __('Sobre nosotros') }}"
        :eyebrow="__('Conoce a :name', ['name' => \App\Support\SiteContent::name()])"
        subtitle="{{ __('Somos tus compañeros de viaje: una flota moderna, precios claros y un equipo que te acompaña de la entrega a la devolución.') }}"
        :image="\App\Support\SiteContent::about()['stats'][0]['image']"
    >
        <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex flex-wrap gap-3">
                <a href="{{ lroute('site.cars') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand px-6 py-3 font-bold text-white transition hover:bg-brand-dark">
                    {{ __('Ver vehículos') }} <x-site.icon name="arrow-right" class="size-4" />
                </a>
                @if ($chat = \App\Support\SiteContent::contactUrl())
                    <a href="{{ $chat }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border border-white/25 px-6 py-3 font-bold text-white transition hover:bg-white/10">
                        <x-site.icon name="phone" class="size-4" /> {{ __('Escríbenos') }}
                    </a>
                @endif
            </div>
            <dl class="grid grid-cols-3 divide-x divide-white/15 rounded-2xl border border-white/10 bg-white/10 backdrop-blur">
                @foreach (\App\Support\SiteContent::about()['stats'] as $stat)
                    <div class="px-5 py-4 sm:px-7">
                        <dt class="sr-only">{{ $stat['label'] }}</dt>
                        <dd class="text-2xl font-extrabold text-white sm:text-3xl">{{ $stat['number'] }}</dd>
                        <dd class="mt-1 max-w-28 text-xs leading-snug text-white/70 sm:text-sm">{{ $stat['label'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </x-site.page-header>

    <x-site.about class="pt-20" />

    <section class="mt-24 bg-neutral-100">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-extrabold sm:text-4xl">{{ __('¿Por qué elegirnos?') }}</h2>
                <p class="mt-3 text-lg text-neutral-600">{{ __('Todo lo que necesitas para rentar sin complicaciones.') }}</p>
            </div>
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (config('site.why_us') as $item)
                    <div class="rounded-2xl bg-white p-7">
                        <span class="flex size-12 items-center justify-center rounded-xl bg-brand text-white"><x-site.icon :name="$item['icon']" class="size-6" /></span>
                        <h3 class="mt-5 text-lg font-bold">{{ __($item['title']) }}</h3>
                        <p class="mt-2 leading-relaxed text-neutral-600">{{ __($item['text']) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <x-site.testimonials :testimonials="$testimonials" />

    <section class="mx-auto max-w-7xl px-4 pb-24 sm:px-6">
        <div class="flex flex-col items-start justify-between gap-6 rounded-2xl bg-black p-10 text-white md:flex-row md:items-center">
            <div>
                <h2 class="text-3xl font-extrabold">{{ __('¿Listo para tu próximo viaje?') }}</h2>
                <p class="mt-2 text-white/70">{{ __('Elige tu vehículo y resérvalo en minutos.') }}</p>
            </div>
            <a href="{{ lroute('site.cars') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand px-6 py-3 font-bold hover:bg-brand-dark">{{ __('Ver vehículos') }} <x-site.icon name="arrow-right" class="size-4" /></a>
        </div>
    </section>
</x-site.layout>
