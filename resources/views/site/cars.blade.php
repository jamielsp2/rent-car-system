@php
    // Filters in use, shown on the mobile "Filtros" button.
    $activeFilters = collect($filters)->except('sort')->sum(fn ($value) => is_array($value) ? count($value) : 1);
    $chip = 'flex cursor-pointer items-center gap-2 rounded-full border border-black/10 bg-white px-3.5 py-2 text-sm font-medium transition select-none hover:border-black/30 has-[:checked]:border-brand has-[:checked]:bg-brand has-[:checked]:text-white has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-brand';
    $field = 'w-full rounded-xl border border-black/10 bg-white px-3 py-2.5 text-base focus:border-brand focus:ring-brand sm:text-sm';
@endphp
@php
    $seo = ['schema' => \App\Support\Seo::trail(__('Nuestros vehículos'), 'site.cars')];
@endphp
<x-site.layout title="{{ __('Nuestros vehículos') }}" :description="__('Vehículos listos para tu próximo viaje. Elige tus fechas y reserva en minutos.')" :seo="$seo">
    <x-site.page-header
        title="{{ __('Nuestros vehículos') }}"
        eyebrow="{{ __('Flota disponible') }}"
        subtitle="{{ __('Vehículos listos para tu próximo viaje. Elige tus fechas y reserva en minutos.') }}"
        :image="$heroImage"
    >
        <div class="grid max-w-3xl grid-cols-3 gap-2 sm:gap-3">
            <div class="flex flex-col gap-2 rounded-2xl border border-white/10 bg-white/10 p-3 backdrop-blur sm:flex-row sm:items-center sm:gap-4 sm:p-4">
                <span class="hidden size-11 shrink-0 items-center justify-center rounded-xl bg-brand text-white sm:flex"><x-site.icon name="search" class="size-5" /></span>
                <p id="car-count" class="leading-tight text-white">
                    <span class="block text-xl font-extrabold sm:text-2xl">{{ $cars->total() }}</span>
                    <span class="text-xs text-white/70 sm:text-sm">{{ $cars->total() === 1 ? __('vehículo encontrado') : __('vehículos encontrados') }}</span>
                </p>
            </div>
            @if ($minPrice)
                <div class="flex flex-col gap-2 rounded-2xl border border-white/10 bg-white/10 p-3 backdrop-blur sm:flex-row sm:items-center sm:gap-4 sm:p-4">
                    <span class="hidden size-11 shrink-0 items-center justify-center rounded-xl bg-white text-brand sm:flex"><x-site.icon name="star" class="size-5" /></span>
                    <p class="leading-tight text-white">
                        <span class="block text-xl font-extrabold sm:text-2xl">{{ $minPrice }}</span>
                        <span class="text-xs text-white/70 sm:text-sm">{{ __('desde, por día') }}</span>
                    </p>
                </div>
            @endif
            <div class="flex flex-col gap-2 rounded-2xl border border-white/10 bg-white/10 p-3 backdrop-blur sm:flex-row sm:items-center sm:gap-4 sm:p-4">
                <span class="hidden size-11 shrink-0 items-center justify-center rounded-xl bg-white text-brand sm:flex"><x-site.icon name="gear" class="size-5" /></span>
                <p class="leading-tight text-white">
                    <span class="block text-xl font-extrabold sm:text-2xl">{{ $types->count() }}</span>
                    <span class="text-xs text-white/70 sm:text-sm">{{ $types->count() === 1 ? __('categoría') : __('categorías') }}</span>
                </p>
            </div>
        </div>
    </x-site.page-header>

    {{-- Mobile toolbar: filters sheet and sort, always at hand --}}
    <div class="sticky top-[81px] z-30 border-b border-black/5 bg-white/95 backdrop-blur lg:hidden">
        <div class="mx-auto flex max-w-7xl items-center gap-2 px-4 py-3 sm:px-6">
            <button type="button" data-filters-open class="relative flex items-center gap-2 rounded-xl bg-black px-4 py-2.5 text-sm font-bold text-white active:scale-95">
                <x-site.icon name="gear" class="size-4" /> {{ __('Filtros') }}
                <span id="filter-count" @class(['flex size-5 items-center justify-center rounded-full bg-brand text-[11px]', 'hidden' => ! $activeFilters])>{{ $activeFilters }}</span>
            </button>
            <label class="relative min-w-0 flex-1">
                <span class="sr-only">{{ __('Ordenar') }}</span>
                <select name="sort" form="car-filters" class="{{ $field }} truncate pr-8" data-mobile-sort>
                    <option value="recent" @selected(($filters['sort'] ?? '') === 'recent')>{{ __('Más recientes') }}</option>
                    <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>{{ __('Menor precio') }}</option>
                    <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>{{ __('Mayor precio') }}</option>
                    <option value="name" @selected(($filters['sort'] ?? '') === 'name')>{{ __('Nombre') }}</option>
                </select>
            </label>
        </div>
    </div>

    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[290px_1fr] lg:py-12">
        {{-- Backdrop of the mobile sheet --}}
        <div data-filters-backdrop class="pointer-events-none fixed inset-0 z-[55] bg-black/50 opacity-0 transition-opacity duration-300 lg:hidden"></div>

        {{-- Filters: a bottom sheet on phones, a sticky sidebar on desktop --}}
        <form id="car-filters" method="GET" data-filters-sheet
            class="fixed inset-x-0 bottom-0 z-[60] flex max-h-[88dvh] translate-y-full flex-col rounded-t-3xl bg-white shadow-2xl transition-transform duration-300 ease-out lg:sticky lg:top-28 lg:z-auto lg:max-h-[calc(100vh-8rem)] lg:translate-y-0 lg:self-start lg:rounded-2xl lg:border lg:border-black/10 lg:shadow-none">
            <div class="relative flex items-center justify-between border-b border-black/5 px-5 pt-6 pb-4 lg:hidden">
                <span class="absolute top-2 left-1/2 h-1.5 w-10 -translate-x-1/2 rounded-full bg-neutral-200"></span>
                <h2 class="text-lg font-extrabold">{{ __('Filtros') }}</h2>
                <button type="button" data-filters-close class="flex size-9 items-center justify-center rounded-full bg-neutral-100 text-xl leading-none" aria-label="{{ __('Cerrar filtros') }}">{{ __('&times;') }}</button>
            </div>

            <div class="flex-1 space-y-7 overflow-y-auto overscroll-contain px-5 py-6 lg:p-6">
                <fieldset>
                    <legend class="mb-3 text-sm font-bold tracking-wide uppercase">{{ __('Fechas') }}</legend>
                    <div class="grid grid-cols-2 gap-2 lg:grid-cols-1">
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-neutral-500">{{ __('Salida') }}</span>
                            <input type="date" name="from" min="{{ \App\Support\LocalTime::today()->toDateString() }}" value="{{ $filters['from'] ?? '' }}" class="{{ $field }}">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-neutral-500">{{ __('Devolución') }}</span>
                            <input type="date" name="to" min="{{ \App\Support\LocalTime::today()->addDay()->toDateString() }}" value="{{ $filters['to'] ?? '' }}" class="{{ $field }}">
                        </label>
                    </div>
                    <p class="mt-2 text-xs text-neutral-500">{{ __('Solo verás vehículos libres en esas fechas.') }}</p>
                </fieldset>

                <fieldset>
                    <legend class="mb-3 text-sm font-bold tracking-wide uppercase">{{ __('Tipo') }}</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($types as $type)
                            <label class="{{ $chip }}">
                                <input type="checkbox" name="type[]" value="{{ $type->id }}" @checked(in_array($type->id, $filters['type'] ?? [])) class="sr-only">
                                {{ $type->name }} <span class="text-xs opacity-60">{{ $type->cars_count }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="mb-3 text-sm font-bold tracking-wide uppercase">{{ __('Precio por día') }}</legend>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" inputmode="numeric" name="min_price" min="0" placeholder="{{ __('Mín.') }}" value="{{ $filters['min_price'] ?? '' }}" class="{{ $field }}" aria-label="{{ __('Precio mínimo') }}">
                        <input type="number" inputmode="numeric" name="max_price" min="0" placeholder="{{ __('Máx. :amount', ['amount' => $maxRate]) }}" value="{{ $filters['max_price'] ?? '' }}" class="{{ $field }}" aria-label="{{ __('Precio máximo') }}">
                    </div>
                </fieldset>

                @if ($transmissions->isNotEmpty())
                    <fieldset>
                        <legend class="mb-3 text-sm font-bold tracking-wide uppercase">{{ __('Transmisión') }}</legend>
                        <div class="flex flex-wrap gap-2">
                            <label class="{{ $chip }}"><input type="radio" name="transmission" value="" @checked(empty($filters['transmission'])) class="sr-only"> {{ __('Todas') }}</label>
                            @foreach ($transmissions as $transmission)
                                <label class="{{ $chip }}">
                                    <input type="radio" name="transmission" value="{{ $transmission->id }}" @checked((int) ($filters['transmission'] ?? 0) === $transmission->id) class="sr-only">
                                    {{ $transmission->name }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                <fieldset>
                    <legend class="mb-3 text-sm font-bold tracking-wide uppercase">{{ __('Marca') }}</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($makes as $make)
                            <label class="{{ $chip }}">
                                <input type="checkbox" name="make[]" value="{{ $make->id }}" @checked(in_array($make->id, $filters['make'] ?? [])) class="sr-only">
                                {{ $make->name }} <span class="text-xs opacity-60">{{ $make->cars_count }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="hidden lg:block">
                    <legend class="mb-3 text-sm font-bold tracking-wide uppercase">{{ __('Ordenar') }}</legend>
                    <select name="sort" class="{{ $field }}" data-desktop-sort>
                        <option value="recent" @selected(($filters['sort'] ?? '') === 'recent')>{{ __('Más recientes') }}</option>
                        <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>{{ __('Precio: menor a mayor') }}</option>
                        <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>{{ __('Precio: mayor a menor') }}</option>
                        <option value="name" @selected(($filters['sort'] ?? '') === 'name')>{{ __('Nombre') }}</option>
                    </select>
                </fieldset>
            </div>

            <div class="flex items-center gap-3 border-t border-black/5 bg-white px-5 py-4 pb-[max(1rem,env(safe-area-inset-bottom))] lg:rounded-b-2xl lg:px-6">
                <a href="{{ lroute('site.cars') }}" class="shrink-0 rounded-xl px-3 py-3 text-sm font-semibold text-neutral-500 hover:text-brand">{{ __('Limpiar') }}</a>
                <button type="button" data-filters-close class="flex-1 rounded-xl bg-brand py-3 font-bold text-white transition hover:bg-brand-dark lg:hidden">
                    {{ __('Ver') }} <span data-results-total>{{ $cars->total() }}</span> {{ __('vehículos') }}
                </button>
                <noscript><button class="flex-1 rounded-xl bg-brand py-3 font-bold text-white hover:bg-brand-dark">{{ __('Aplicar filtros') }}</button></noscript>
            </div>
        </form>

        <div id="car-results" class="min-w-0 transition-opacity duration-200" aria-live="polite" data-total="{{ $cars->total() }}" data-active="{{ $activeFilters }}">
            @if ($notice)
                <p class="mb-6 rounded-xl bg-amber-50 p-4 text-amber-800">{{ $notice }} {{ __('Mostramos todos los vehículos.') }}</p>
            @elseif (! empty($filters['from']))
                <p class="mb-6 flex items-center gap-3 rounded-xl bg-neutral-100 p-4 text-neutral-700">
                    <x-site.icon name="calendar" class="size-5 text-brand" />
                    <span>{{ __('Vehículos libres del') }} <strong>{{ \App\Support\Lang::numericDate(\Illuminate\Support\Carbon::parse($filters['from'])) }}</strong>
                    @if (! empty($filters['to'])) {{ __('al') }} <strong>{{ \App\Support\Lang::numericDate(\Illuminate\Support\Carbon::parse($filters['to'])) }}</strong>@endif.</span>
                </p>
            @endif

            @if ($cars->isEmpty())
                <div class="rounded-2xl border border-dashed border-black/15 p-10 text-center">
                    <p class="text-lg font-bold">{{ __('No hay vehículos con esos filtros') }}</p>
                    <p class="mt-1 text-neutral-600">{{ __('Prueba con otras fechas o quita algún filtro.') }}</p>
                    <a href="{{ lroute('site.cars') }}" class="mt-5 inline-block rounded-xl bg-black px-5 py-3 text-sm font-bold text-white">{{ __('Ver todos los vehículos') }}</a>
                </div>
            @else
                <div class="grid gap-5 sm:grid-cols-2 sm:gap-6 xl:grid-cols-3">
                    @foreach ($cars as $car)
                        <x-site.car-card :car="$car" :query="array_intersect_key($filters, array_flip(['from', 'to']))" />
                    @endforeach
                </div>
                <div class="mt-10">{{ $cars->links() }}</div>
            @endif
        </div>
    </div>

    <script>
        // Filters apply as soon as they change; results refresh in place.
        (function () {
            const form = document.getElementById('car-filters');
            const results = document.getElementById('car-results');
            const count = document.getElementById('car-count');
            const badge = document.getElementById('filter-count');
            const totals = document.querySelectorAll('[data-results-total]');
            const backdrop = document.querySelector('[data-filters-backdrop]');
            const desktop = window.matchMedia('(min-width: 1024px)');
            let pending, timer;

            // Only one sort control may count: the toolbar one on phones, the sidebar one on desktop.
            const syncSort = () => {
                form.querySelector('[data-desktop-sort]').disabled = ! desktop.matches;
                document.querySelector('[data-mobile-sort]').disabled = desktop.matches;
            };
            syncSort();
            desktop.addEventListener('change', syncSort);

            function sheet(open) {
                form.classList.toggle('translate-y-full', ! open);
                backdrop.classList.toggle('opacity-0', ! open);
                backdrop.classList.toggle('pointer-events-none', ! open);
                document.documentElement.classList.toggle('overflow-hidden', open && ! desktop.matches);
            }

            document.querySelectorAll('[data-filters-open]').forEach((button) => button.addEventListener('click', () => sheet(true)));
            document.querySelectorAll('[data-filters-close]').forEach((button) => button.addEventListener('click', () => {
                sheet(false);
                results.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }));
            backdrop.addEventListener('click', () => sheet(false));
            document.addEventListener('keydown', (event) => event.key === 'Escape' && sheet(false));

            async function apply() {
                const params = new URLSearchParams([...new FormData(form)].filter(([, value]) => value !== ''));
                const url = form.action.split('?')[0] + (params.toString() ? '?' + params : '');

                pending?.abort();
                pending = new AbortController();
                results.classList.add('opacity-50');

                try {
                    const response = await fetch(url, { signal: pending.signal });
                    const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const fresh = page.getElementById('car-results');
                    results.innerHTML = fresh.innerHTML;
                    count.innerHTML = page.getElementById('car-count').innerHTML;
                    totals.forEach((el) => el.textContent = fresh.dataset.total);
                    badge.textContent = fresh.dataset.active;
                    badge.classList.toggle('hidden', fresh.dataset.active === '0');
                    history.replaceState(null, '', url);
                } catch (e) {
                    if (e.name !== 'AbortError') form.submit();
                } finally {
                    results.classList.remove('opacity-50');
                }
            }

            // Fields linked with form="car-filters" live outside the form, so listen on the whole page.
            document.addEventListener('change', (event) => {
                if (event.target.form !== form) return;
                clearTimeout(timer);
                // Give typed prices a moment; chips, dates and selects apply right away.
                timer = setTimeout(apply, event.target.type === 'number' ? 500 : 0);
            });
        })();
    </script>
</x-site.layout>
