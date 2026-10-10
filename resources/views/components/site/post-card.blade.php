@props(['post'])
<article class="group flex flex-col overflow-hidden rounded-2xl border border-black/10 bg-white transition hover:-translate-y-1 hover:shadow-xl">
    <a href="{{ $post->url }}" class="block aspect-[16/10] overflow-hidden bg-neutral-100">
        @if ($post->image_url)
            <img src="{{ $post->image_url }}" alt="{{ $post->name }}" loading="lazy" class="size-full object-cover transition duration-500 group-hover:scale-105">
        @endif
    </a>
    <div class="flex flex-1 flex-col p-6">
        <p class="text-sm text-neutral-500">{{ $post->created_at?->translatedFormat('d \d\e F, Y') }}</p>
        <h3 class="mt-2 text-xl leading-snug font-bold"><a href="{{ $post->url }}" class="hover:text-brand">{{ $post->name }}</a></h3>
        @if ($post->description)
            <p class="mt-3 line-clamp-3 text-neutral-600">{{ $post->description }}</p>
        @endif
        <a href="{{ $post->url }}" class="mt-auto inline-flex items-center gap-2 pt-5 font-bold hover:text-brand">{{ __('Leer más') }} <x-site.icon name="arrow-right" class="size-4" /></a>
    </div>
</article>
