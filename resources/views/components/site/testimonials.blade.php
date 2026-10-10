@props(['testimonials'])
@if ($testimonials->isNotEmpty())
    <section {{ $attributes->merge(['class' => 'mx-auto max-w-7xl px-4 py-24 sm:px-6']) }}>
        <div class="text-center">
            <span class="rounded-full border border-black/10 px-4 py-1.5 text-sm font-semibold">{{ __('Testimonios') }}</span>
            <h2 class="mt-4 text-3xl font-extrabold sm:text-4xl">{{ __('¿Qué dicen de nosotros?') }}</h2>
        </div>
        <div class="mt-12 grid gap-6 md:grid-cols-3">
            @foreach ($testimonials as $testimonial)
                <figure class="flex flex-col rounded-2xl border border-black/10 p-6">
                    <div class="flex gap-1 text-brand">
                        @for ($i = 0; $i < 5; $i++)
                            <x-site.icon name="star" class="size-4" />
                        @endfor
                    </div>
                    <blockquote class="mt-4 flex-1 leading-relaxed text-neutral-700">{{ trim(strip_tags($testimonial->content)) }}</blockquote>
                    <figcaption class="mt-6 flex items-center gap-3">
                        @if ($testimonial->image)
                            <img src="{{ \App\Support\Media::url($testimonial->image) }}" alt="" class="size-11 rounded-full object-cover">
                        @else
                            <span class="flex size-11 items-center justify-center rounded-full bg-neutral-100 font-bold">{{ mb_substr($testimonial->name, 0, 1) }}</span>
                        @endif
                        <span class="font-bold">{{ $testimonial->name }}</span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </section>
@endif
