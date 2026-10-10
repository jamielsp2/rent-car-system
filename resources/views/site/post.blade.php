@php
    $seo = \App\Support\Seo::post($post);
@endphp
<x-site.layout :title="$seo['title']" :description="$seo['description']" :seo="$seo">
    <article class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <nav class="flex flex-wrap items-center gap-2 text-sm text-neutral-500">
            <a href="{{ lroute('site.home') }}" class="hover:text-brand">{{ __('Inicio') }}</a> <span>/</span>
            <a href="{{ lroute('site.blog') }}" class="hover:text-brand">{{ __('Blog') }}</a>
        </nav>

        <h1 class="mt-5 text-3xl leading-tight font-extrabold sm:text-5xl">{{ $post->name }}</h1>
        <p class="mt-4 text-neutral-500">{{ \App\Support\Lang::date($post->created_at) }}</p>

        @if ($post->image_url)
            <img src="{{ $post->image_url }}" alt="{{ $post->name }}" class="mt-8 aspect-[16/9] w-full rounded-2xl object-cover">
        @endif

        @if ($post->description)
            <p class="mt-8 text-xl leading-relaxed text-neutral-700">{{ $post->description }}</p>
        @endif

        <div class="mt-8 space-y-5 text-lg leading-relaxed text-neutral-800 [&_a]:text-brand [&_a]:underline [&_h2]:mt-10 [&_h2]:text-2xl [&_h2]:font-bold [&_h3]:mt-8 [&_h3]:text-xl [&_h3]:font-bold [&_img]:rounded-xl [&_li]:ml-6 [&_ol]:list-decimal [&_ul]:list-disc">
            {!! $post->safe_content !!}
        </div>

        <div class="mt-12 flex flex-col items-start justify-between gap-4 rounded-2xl bg-neutral-100 p-7 sm:flex-row sm:items-center">
            <p class="text-lg font-bold">{{ __('¿Listo para recorrer la isla?') }}</p>
            <a href="{{ lroute('site.cars') }}" class="rounded-lg bg-brand px-5 py-3 font-bold text-white hover:bg-brand-dark">{{ __('Reserva tu vehículo') }}</a>
        </div>
    </article>

    @if ($recent->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pb-24 sm:px-6">
            <h2 class="text-2xl font-extrabold sm:text-3xl">{{ __('Más artículos') }}</h2>
            <div class="mt-6 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($recent as $item)
                    <x-site.post-card :post="$item" />
                @endforeach
            </div>
        </section>
    @endif
</x-site.layout>
