@props(['title', 'subtitle' => null, 'eyebrow' => null, 'image' => null])
{{-- Inner-page hero: photo under a dark gradient, breadcrumb, title and an optional slot for figures or actions. --}}
@php($image ??= \App\Support\SiteContent::hero()['image'])
<section class="relative isolate overflow-hidden bg-black">
    @if ($image)
        <img src="{{ $image }}" alt="" class="absolute inset-0 -z-20 size-full object-cover opacity-45">
    @endif
    <div class="absolute inset-0 -z-10 bg-gradient-to-r from-black via-black/80 to-black/30"></div>
    <div class="absolute inset-x-0 bottom-0 -z-10 h-24 bg-gradient-to-t from-black/60 to-transparent"></div>
    <div class="pointer-events-none absolute -bottom-32 -left-24 -z-10 size-96 rounded-full bg-brand/30 blur-3xl"></div>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-16 lg:py-24">
        <nav aria-label="Ruta" class="flex items-center gap-2 text-sm text-white/60">
            <a href="{{ route('site.home') }}" class="transition hover:text-white">Inicio</a>
            <x-site.icon name="arrow-right" class="size-3.5" />
            <span class="text-white">{{ $title }}</span>
        </nav>

        @if ($eyebrow)
            <span class="mt-5 inline-block rounded-lg bg-brand px-3 py-1.5 text-xs font-bold text-white sm:mt-6 sm:px-4 sm:py-2 sm:text-sm">{{ $eyebrow }}</span>
        @endif

        <h1 @class(['max-w-3xl text-[2rem] leading-tight font-extrabold text-white sm:text-6xl', 'mt-5' => $eyebrow, 'mt-6' => ! $eyebrow])>{{ $title }}</h1>

        @if ($subtitle)
            <p class="mt-3 max-w-2xl text-base text-white/80 sm:mt-5 sm:text-xl">{{ $subtitle }}</p>
        @endif

        @if (trim($slot))
            <div class="mt-6 sm:mt-10">{{ $slot }}</div>
        @endif
    </div>
</section>
