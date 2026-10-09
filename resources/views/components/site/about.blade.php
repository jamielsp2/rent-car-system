@php
    use App\Support\Media;

    $about = \App\Support\SiteContent::about();
@endphp
<section {{ $attributes->merge(['class' => 'mx-auto max-w-7xl px-4 sm:px-6']) }}>
    <div class="grid gap-8 lg:grid-cols-2 lg:items-start">
        <h2 class="text-3xl font-extrabold sm:text-5xl">{{ $about['title'] }}</h2>
        <p class="text-lg leading-relaxed text-neutral-600">{{ $about['text'] }}</p>
    </div>
    <div class="mt-12 grid gap-6 md:grid-cols-3">
        @foreach ($about['stats'] as $stat)
            @if ($stat['image'])
                <div class="relative h-80 overflow-hidden rounded-2xl">
                    <img src="{{ Media::url($stat['image']) }}" alt="" loading="lazy" class="size-full object-cover">
                    <div class="absolute inset-x-4 bottom-4 flex items-center gap-4 rounded-xl bg-white p-4">
                        <span class="text-4xl font-extrabold">{{ $stat['number'] }}</span>
                        <span class="text-lg leading-tight font-semibold">{{ $stat['label'] }}</span>
                    </div>
                </div>
            @else
                <div class="flex h-80 flex-col justify-center rounded-2xl bg-brand p-8 text-white">
                    <span class="text-7xl font-extrabold">{{ $stat['number'] }}</span>
                    <span class="mt-2 text-2xl font-bold">{{ $stat['label'] }}</span>
                </div>
            @endif
        @endforeach
    </div>
</section>
