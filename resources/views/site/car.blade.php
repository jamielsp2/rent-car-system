@php
    use App\Support\Media;
    use App\Support\LocalTime;
    use App\Support\Money;

    /** @var \App\Models\Car $car */
    $photos = collect($car->images)->map(fn ($path) => Media::url($path))->filter()->values();
    $specs = array_filter([
        'Marca' => $car->make?->name,
        'Modelo' => $car->model_name,
        'Año' => $car->year ?: null,
        'Tipo' => $car->type?->name,
        'Transmisión' => $car->transmission?->name,
        'Combustible' => $car->fuel?->name,
        'Asientos' => $car->number_of_seats ?: null,
        'Puertas' => $car->number_of_doors ?: null,
        'Color' => $car->colors->pluck('name')->implode(', ') ?: null,
    ]);
    $from = $dates['from'] ?? LocalTime::today()->addDay()->toDateString();
    $to = $dates['to'] ?? LocalTime::today()->addDays(max(2, $car->min_rental_days + 1))->toDateString();
@endphp
<x-site.layout :title="$car->name . ' - Alquiler de vehículos'" :description="$car->description">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <nav class="flex flex-wrap items-center gap-2 text-sm text-neutral-500">
            <a href="{{ route('site.home') }}" class="hover:text-brand">Inicio</a> <span>/</span>
            <a href="{{ route('site.cars') }}" class="hover:text-brand">Nuestros vehículos</a> <span>/</span>
            <span class="text-ink">{{ $car->name }}</span>
        </nav>

        {{-- On phones the booking box comes right after the title; on desktop it sits beside everything. --}}
        <div class="mt-6 grid gap-8 md:grid-cols-[1fr_340px] md:gap-x-8 md:gap-y-0 lg:grid-cols-[1fr_380px] lg:gap-x-10">
            <div class="order-1 min-w-0 md:order-none md:col-span-2 md:row-start-1 lg:col-span-1">
                {{-- Gallery --}}
                @if ($photos->isNotEmpty())
                    <div x-data class="space-y-3">
                        <div class="aspect-[16/10] overflow-hidden rounded-2xl bg-neutral-100">
                            <img id="main-photo" src="{{ $photos->first() }}" alt="{{ $car->name }}" class="size-full object-cover">
                        </div>
                        @if ($photos->count() > 1)
                            <div class="grid grid-cols-4 gap-3 sm:grid-cols-6">
                                @foreach ($photos as $photo)
                                    <button type="button" class="aspect-square overflow-hidden rounded-xl ring-brand focus:ring-2"
                                            onclick="document.getElementById('main-photo').src = this.querySelector('img').src">
                                        <img src="{{ $photo }}" alt="" loading="lazy" class="size-full object-cover transition hover:opacity-80">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <div class="order-2 min-w-0 md:order-none md:col-start-1 md:row-start-2 md:mt-8">
                <div>
                    @if ($car->type)
                        <span class="rounded-full bg-neutral-100 px-3 py-1 text-sm font-semibold">{{ $car->type->name }}</span>
                    @endif
                    <h1 class="mt-3 text-3xl font-extrabold sm:text-4xl">{{ $car->name }}</h1>
                    @if ($car->description)
                        <p class="mt-3 text-lg text-neutral-600">{{ $car->description }}</p>
                    @endif
                </div>
            </div>

            <div class="order-4 min-w-0 md:order-none md:col-start-1 md:row-start-3">
                @if ($specs)
                    <section class="md:mt-8">
                        <h2 class="text-xl font-bold">Características</h2>
                        <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach ($specs as $label => $value)
                                <div class="rounded-xl border border-black/10 p-4">
                                    <dt class="text-sm text-neutral-500">{{ $label }}</dt>
                                    <dd class="mt-1 font-bold">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @endif

                @if (trim(strip_tags($car->safe_content)))
                    <section class="mt-8">
                        <h2 class="text-xl font-bold">Descripción</h2>
                        <div class="mt-4 space-y-4 leading-relaxed text-neutral-700 [&_li]:ml-5 [&_ul]:list-disc">{!! $car->safe_content !!}</div>
                    </section>
                @endif

                @if ($car->insurance_info)
                    <section class="mt-8 rounded-2xl bg-neutral-100 p-6">
                        <h2 class="text-xl font-bold">Seguro</h2>
                        <p class="mt-2 text-neutral-700">{{ $car->insurance_info }}</p>
                    </section>
                @endif
            </div>

            {{-- Booking box: the price refreshes as soon as dates or extras change. --}}
            <aside id="reservar" class="order-3 scroll-mt-28 md:order-none md:col-start-2 md:row-span-2 md:row-start-2 md:sticky md:top-28 md:self-start lg:row-span-3 lg:row-start-1">
                <div class="rounded-2xl border border-black/10 p-5 shadow-lg sm:p-6">
                    <p class="text-xs font-bold tracking-wide text-brand uppercase">Reserva este vehículo</p>
                    <p class="mt-1"><span class="text-3xl font-extrabold">{{ Money::display($car->rental_rate) }}</span> <span class="text-neutral-500">/ día</span></p>
                    @if ($car->min_rental_days > 1)
                        <p class="mt-1 text-sm text-neutral-500">Mínimo {{ $car->min_rental_days }} días de renta</p>
                    @endif

                    <form id="quote-form" method="GET" action="{{ $car->public_url }}" data-quote-url="{{ route('site.car.quote', $car->slug->key) }}" class="mt-5 space-y-5 border-t border-black/10 pt-5">
                        <input type="hidden" name="from" value="{{ $from }}">
                        <input type="hidden" name="to" value="{{ $to }}">

                        <div>
                            <span class="mb-3 flex items-center gap-2 text-sm font-bold"><span class="flex size-6 items-center justify-center rounded-full bg-black text-xs text-white">1</span> Elige tus fechas</span>
                            <div id="rental-calendar" data-booked='@json($unavailable)' data-min-days="{{ max(1, (int) $car->min_rental_days) }}"></div>
                            <p id="calendar-summary" class="mt-3 text-sm text-neutral-700"></p>
                            <p id="calendar-warning" class="mt-2 hidden rounded-lg bg-red-50 p-2.5 text-sm text-red-700" role="alert"></p>
                            {{-- Legend swatches look exactly like the calendar days they describe. --}}
                            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-neutral-600">
                                <span class="flex items-center gap-1.5">
                                    <span class="flex h-6 w-7 items-center justify-center rounded-md bg-brand text-[11px] font-semibold text-white">12</span> Tus fechas
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="flex h-6 w-7 items-center justify-center rounded-md border border-red-200 bg-red-100 text-[11px] font-semibold text-red-600 line-through">12</span> No disponible
                                </span>
                            </div>
                        </div>

                        @if ($services->isNotEmpty())
                            <div>
                            <span class="mb-3 flex items-center gap-2 text-sm font-bold"><span class="flex size-6 items-center justify-center rounded-full bg-black text-xs text-white">2</span> Agrega extras <span class="font-normal text-neutral-500">(opcional)</span></span>
                            <details class="group rounded-lg border border-black/10" @if ($selected) open @endif>
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-semibold">
                                    <span>
                                        Ver servicios disponibles
                                        <span id="services-count" class="ml-1 font-normal text-neutral-500">{{ count($selected) ? '(' . count($selected) . ' ' . (count($selected) === 1 ? 'elegido' : 'elegidos') . ')' : '' }}</span>
                                    </span>
                                    <svg class="size-4 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                                </summary>
                                <div class="space-y-2 border-t border-black/10 p-3">
                                    @foreach ($services as $service)
                                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-black/10 px-3 py-2.5 has-[:checked]:border-brand has-[:checked]:bg-red-50/40">
                                            <input type="checkbox" name="services[]" value="{{ $service->id }}" @checked(in_array($service->id, $selected)) class="accent-brand">
                                            <span class="flex-1 text-sm font-semibold">{{ $service->name }}</span>
                                            <span class="text-sm font-bold whitespace-nowrap">
                                                {{ Money::display($service->price) }}<span class="font-normal text-neutral-500">{{ $service->price_type === \App\Enums\ServicePriceType::PerDay ? '/día' : '' }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </details>
                            </div>
                        @endif

                        <noscript><button class="w-full rounded-lg border-2 border-ink py-2.5 font-bold">Calcular precio</button></noscript>
                    </form>

                    <div class="mt-5 border-t border-black/10 pt-5">
                        <span class="mb-3 flex items-center gap-2 text-sm font-bold"><span class="flex size-6 items-center justify-center rounded-full bg-black text-xs text-white">{{ $services->isNotEmpty() ? 3 : 2 }}</span> Revisa el total y reserva</span>
                    </div>
                    <div id="quote" class="transition-opacity" aria-live="polite">
                        @include('site.partials.quote')
                    </div>

                    @if ($chat = \App\Support\SiteContent::contactUrl('Hola, me interesa rentar el ' . $car->name))
                        <a href="{{ $chat }}" target="_blank" rel="noopener"
                           class="mt-3 block rounded-lg bg-[#25d366] py-3 text-center font-bold text-white transition hover:opacity-90">Consultar por WhatsApp</a>
                    @endif
                </div>
            </aside>
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-20">
                <h2 class="text-2xl font-extrabold sm:text-3xl">También te puede interesar</h2>
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($related as $item)
                        <x-site.car-card :car="$item" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- Phones: price and a shortcut to the booking box, hidden while the box is on screen --}}
    <div data-book-bar class="fixed inset-x-0 bottom-0 z-40 translate-y-full border-t border-black/10 bg-white/95 backdrop-blur transition-transform duration-300 md:hidden">
        <div class="flex items-center gap-3 py-3 pr-24 pl-4 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
            <div class="min-w-0 flex-1 leading-tight">
                <p class="truncate text-xs text-neutral-500">{{ $car->name }}</p>
                <p><span class="text-lg font-extrabold">{{ Money::display($car->rental_rate) }}</span> <span class="text-sm text-neutral-500">/ día</span></p>
            </div>
            <a href="#reservar" class="shrink-0 rounded-xl bg-brand px-5 py-3 text-sm font-bold text-white active:scale-95">Reservar</a>
        </div>
    </div>

    <script>
        (function () {
            const bar = document.querySelector('[data-book-bar]');
            const box = document.getElementById('reservar');
            if (!bar || !box || !('IntersectionObserver' in window)) return;

            // Show the bar once the visitor scrolls past the title, hide it over the booking box.
            new IntersectionObserver(([entry]) => {
                const past = entry.boundingClientRect.top < 0 || entry.isIntersecting;
                bar.classList.toggle('translate-y-full', entry.isIntersecting || ! past);
            }).observe(box);

            bar.querySelector('a').addEventListener('click', (event) => {
                event.preventDefault();
                box.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        })();
    </script>

    <script>
        (function () {
            const form = document.getElementById('quote-form');
            const box = document.getElementById('quote');
            if (!form || !box) return;

            let pending;

            async function refresh() {
                const params = new URLSearchParams(new FormData(form));
                if (!params.get('from') || !params.get('to')) return;

                pending?.abort();
                pending = new AbortController();
                box.classList.add('opacity-50');

                try {
                    const response = await fetch(form.dataset.quoteUrl + '?' + params, { signal: pending.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (response.ok) {
                        box.innerHTML = await response.text();
                        history.replaceState(null, '', form.action + '?' + params);
                    }
                } catch (e) {
                    if (e.name !== 'AbortError') console.error(e);
                } finally {
                    box.classList.remove('opacity-50');
                }
            }

            const servicesCount = document.getElementById('services-count');
            form.addEventListener('change', () => {
                if (!servicesCount) return;
                const n = form.querySelectorAll('input[name="services[]"]:checked').length;
                servicesCount.textContent = n ? `(${n} ${n === 1 ? 'elegido' : 'elegidos'})` : '';
            });

            form.addEventListener('change', refresh);
            form.addEventListener('submit', (e) => { e.preventDefault(); refresh(); });

            @unless ($quote)
                refresh();
            @endunless
        })();
    </script>
    @vite('resources/js/car-calendar.js')
</x-site.layout>
