@props(['car', 'query' => []])
@php
    /** @var \App\Models\Car $car */
    // Carry the searched dates into the car page.
    $url = $car->public_url . ($query ? '?' . http_build_query($query) : '');
    $specs = array_filter([
        ['icon' => 'gear', 'text' => $car->transmission?->name],
        ['icon' => 'fuel', 'text' => $car->fuel?->name],
        ['icon' => 'seat', 'text' => $car->number_of_seats ? __(':count asientos', ['count' => $car->number_of_seats]) : null],
        ['icon' => 'calendar', 'text' => $car->year ?: null],
    ], fn ($spec) => $spec['text']);
@endphp
{{-- The whole card is the link, so it can be tapped anywhere. --}}
<a href="{{ $url }}" class="group flex flex-col overflow-hidden rounded-2xl border border-black/10 bg-white transition duration-300 hover:-translate-y-1 hover:shadow-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand active:scale-[0.99]">
    <div class="relative aspect-[16/10] overflow-hidden bg-neutral-100">
        @if ($car->cover_url)
            <img src="{{ $car->cover_url }}" alt="{{ $car->name }}" loading="lazy" class="size-full object-cover transition duration-500 group-hover:scale-105">
        @endif
        @if ($car->type)
            <span class="absolute top-3 left-3 rounded-full bg-white/95 px-3 py-1 text-xs font-semibold">{{ $car->type->name }}</span>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-5">
        <p class="text-sm text-neutral-500">{{ $car->make?->name }}</p>
        <h3 class="text-lg leading-snug font-bold transition group-hover:text-brand">{{ $car->name }}</h3>
        @if ($specs)
            <ul class="mt-4 grid grid-cols-2 gap-x-3 gap-y-2 border-t border-black/5 pt-4 text-sm text-neutral-600">
                @foreach ($specs as $spec)
                    <li class="flex items-center gap-2"><x-site.icon :name="$spec['icon']" class="size-4 text-neutral-400" /> {{ $spec['text'] }}</li>
                @endforeach
            </ul>
        @endif
        <div class="mt-auto flex items-end justify-between gap-3 pt-5">
            <p><span class="text-2xl font-extrabold">{{ \App\Support\Money::display($car->rental_rate) }}</span> <span class="text-sm text-neutral-500">{{ __('/ día') }}</span></p>
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-neutral-100 px-4 py-2 text-sm font-bold transition group-hover:bg-brand group-hover:text-white">
                {{ __('Reservar') }} <x-site.icon name="arrow-right" class="size-4" />
            </span>
        </div>
    </div>
</a>
