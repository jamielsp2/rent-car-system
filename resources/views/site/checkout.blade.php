@php
    use App\Support\Money;
    use Illuminate\Support\Carbon;

    $input = 'w-full rounded-xl border border-black/10 px-3.5 py-3 text-base focus:border-brand focus:ring-brand sm:text-sm';
    $from = Carbon::parse($dates['from']);
    $to = Carbon::parse($dates['to']);
    $backUrl = $car->public_url . '?' . http_build_query(['from' => $dates['from'], 'to' => $dates['to'], 'services' => $selected]);
    $number = 'flex size-7 shrink-0 items-center justify-center rounded-full bg-black text-sm font-bold text-white';
    $period = $from->translatedFormat('j M') . ' → ' . $to->translatedFormat('j M Y') . ' · ' . $quote['days'] . ' ' . ($quote['days'] === 1 ? __('día') : __('días'));
@endphp
<x-site.layout :title="__('Reservar') . ' ' . $car->name">
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10">
        <a href="{{ $backUrl }}" class="inline-flex items-center gap-1 text-sm text-neutral-500 hover:text-brand">{{ __('&larr; Volver al vehículo') }}</a>
        <h1 class="mt-3 text-2xl font-extrabold sm:text-4xl">{{ __('Completa tu reserva') }}</h1>

        {{-- Where the customer is in the process --}}
        <ol class="mt-5 grid grid-cols-3 gap-2 text-xs font-semibold sm:text-sm">
            <li class="flex flex-col gap-2">
                <span class="h-1.5 rounded-full bg-brand"></span>
                <span class="flex items-center gap-1 text-neutral-500"><x-site.icon name="check" class="size-4 text-brand" /> {{ __('Vehículo y fechas') }}</span>
            </li>
            <li class="flex flex-col gap-2">
                <span class="h-1.5 rounded-full bg-brand"></span>
                <span class="text-black">{{ __('2. Tus datos y pago') }}</span>
            </li>
            <li class="flex flex-col gap-2">
                <span class="h-1.5 rounded-full bg-neutral-200"></span>
                <span class="text-neutral-400">{{ __('3. Confirmación') }}</span>
            </li>
        </ol>

        {{-- Phones: what is being booked, before the form --}}
        <div class="mt-6 flex gap-4 rounded-2xl border border-black/10 p-3 lg:hidden">
            @if ($car->cover_url)
                <img src="{{ $car->cover_url }}" alt="" class="h-20 w-28 shrink-0 rounded-xl object-cover">
            @endif
            <div class="min-w-0 flex-1">
                <p class="truncate font-bold">{{ $car->name }}</p>
                <p class="text-sm text-neutral-600">{{ $period }}</p>
                <div class="mt-1 flex items-baseline justify-between gap-2">
                    <span class="text-lg font-extrabold">{{ Money::display($quote['total']) }}</span>
                    <a href="{{ $backUrl }}" class="text-sm font-semibold text-brand">{{ __('Cambiar') }}</a>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="mt-6 rounded-xl bg-red-50 p-4 text-red-700" role="alert">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ lroute('site.checkout.store', $car->slug->key) }}" class="mt-6 grid gap-6 lg:mt-8 lg:grid-cols-[1fr_380px] lg:gap-8">
            @csrf
            <input type="hidden" name="from" value="{{ $dates['from'] }}">
            <input type="hidden" name="to" value="{{ $dates['to'] }}">
            @foreach ($selected as $serviceId)
                <input type="hidden" name="services[]" value="{{ $serviceId }}">
            @endforeach

            <div class="space-y-6">
                <section class="rounded-2xl border border-black/10 p-5 sm:p-6">
                    <h2 class="flex items-center gap-3 text-xl font-bold"><span class="{{ $number }}">1</span> {{ __('Tus datos') }}</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <label class="block sm:col-span-2">
                            <span class="mb-1 block text-sm font-semibold">{{ __('Nombre completo *') }}</span>
                            <input name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name" class="{{ $input }}">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-sm font-semibold">{{ __('Correo electrónico *') }}</span>
                            <input type="email" name="email" value="{{ old('email') }}" required maxlength="120" autocomplete="email" inputmode="email" class="{{ $input }}">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-sm font-semibold">{{ __('Teléfono / WhatsApp *') }}</span>
                            <input type="tel" name="phone" value="{{ old('phone') }}" required maxlength="25" autocomplete="tel" inputmode="tel" class="{{ $input }}">
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1 block text-sm font-semibold">{{ __('Hora de entrega y devolución *') }}</span>
                            <select name="pickup_time" class="{{ $input }}">
                                @foreach (range(8, 17) as $hour)
                                    @php $value = sprintf('%02d:00', $hour); @endphp
                                    <option value="{{ $value }}" @selected(old('pickup_time', '09:00') === $value)>{{ Carbon::createFromFormat('H:i', $value)->format('h:i A') }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1 block text-sm font-semibold">{{ __('Nota (opcional)') }}</span>
                            <textarea name="note" rows="3" maxlength="1000" placeholder="{{ __('Lugar de entrega, vuelo, preguntas...') }}" class="{{ $input }}">{{ old('note') }}</textarea>
                        </label>
                    </div>
                </section>

                <section class="rounded-2xl border border-black/10 p-5 sm:p-6">
                    <h2 class="flex items-center gap-3 text-xl font-bold"><span class="{{ $number }}">2</span> {{ __('Forma de pago') }}</h2>
                    <div class="mt-5 space-y-3">
                        @foreach ($methods as $value => $method)
                            <label class="flex cursor-pointer gap-3 rounded-xl border border-black/10 p-4 transition has-[:checked]:border-brand has-[:checked]:bg-red-50/40">
                                <input type="radio" name="payment_method" value="{{ $value }}" @checked(old('payment_method', array_key_first($methods)) === $value) class="mt-0.5 size-5 shrink-0 accent-brand">
                                <span>
                                    <span class="block font-bold">{{ $method['label'] }}</span>
                                    @if ($method['instructions'])
                                        <span class="mt-1 block text-sm text-neutral-600">{{ $method['instructions'] }}</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </section>
            </div>

            <aside class="lg:sticky lg:top-28 lg:self-start">
                <div class="rounded-2xl border border-black/10 p-5 shadow-lg sm:p-6">
                    <h2 class="flex items-center gap-3 text-xl font-bold lg:hidden"><span class="{{ $number }}">3</span> {{ __('Confirma') }}</h2>
                    <div class="hidden lg:block">
                        @if ($car->cover_url)
                            <img src="{{ $car->cover_url }}" alt="{{ $car->name }}" class="aspect-[16/10] w-full rounded-xl object-cover">
                        @endif
                        <p class="mt-4 text-sm text-neutral-500">{{ $car->make?->name }}</p>
                        <h2 class="text-xl font-bold">{{ $car->name }}</h2>
                        <p class="mt-2 flex items-center gap-2 text-sm text-neutral-600">
                            <x-site.icon name="calendar" class="size-4 text-brand" /> {{ $period }}
                        </p>
                    </div>

                    <div class="mt-5 space-y-2 border-t border-black/10 pt-5 text-sm">
                        <div class="flex justify-between"><span>{{ Money::display($quote['rate']) }} × {{ $quote['days'] }} {{ $quote['days'] === 1 ? __('día') : __('días') }}</span><span>{{ Money::display($quote['rental']) }}</span></div>
                        @foreach ($quote['services'] as $service)
                            <div class="flex justify-between text-neutral-600"><span>{{ $service['name'] }}</span><span>{{ Money::display($service['amount']) }}</span></div>
                        @endforeach
                        @foreach ($quote['taxes'] as $tax)
                            <div class="flex justify-between text-neutral-600"><span>{{ $tax['name'] }}</span><span>{{ Money::display($tax['amount']) }}</span></div>
                        @endforeach
                        <div class="flex justify-between border-t border-black/10 pt-2 text-lg font-extrabold"><span>{{ __('Total') }}</span><span>{{ Money::display($quote['total']) }}</span></div>
                        @unless (Money::current()?->is_default ?? true)
                            <p class="text-neutral-500">{{ __('Tu reserva se registra en dólares: :total USD. El monto en :currency es referencial.', ['total' => Money::format($quote['total']), 'currency' => Money::current()->title]) }}</p>
                        @endunless
                        @if ($quote['deposit'] > 0)
                            <p class="text-neutral-500">{{ __('+ Depósito de garantía reembolsable: :amount', ['amount' => Money::display($quote['deposit'])]) }}</p>
                        @endif
                    </div>

                    <label class="mt-5 flex cursor-pointer gap-3 rounded-xl bg-neutral-100 p-4 text-sm text-neutral-700">
                        <input type="checkbox" name="terms" value="1" @checked(old('terms')) required class="mt-0.5 size-5 shrink-0 accent-brand">
                        <span>{{ __('Acepto los términos y condiciones del alquiler. El contrato se firma al recibir el vehículo, presentando licencia de conducir vigente y documento de identidad.') }}</span>
                    </label>

                    <button class="mt-5 w-full rounded-xl bg-brand py-4 text-base font-bold text-white transition hover:bg-brand-dark active:scale-[0.99]">{{ __('Confirmar reserva') }}</button>
                    <p class="mt-3 flex items-center justify-center gap-1.5 text-center text-xs text-neutral-500">
                        <x-site.icon name="check" class="size-4 text-green-600" /> {{ __('No se cobra nada ahora. Te contactaremos para confirmar.') }}
                    </p>
                </div>
            </aside>
        </form>
    </div>
</x-site.layout>
