@php
    use App\Support\Money;

    $rental = $booking->rental;
    $message = __('Hola, acabo de hacer la reserva :number del :car (:from - :to). Total: :total. Nombre: :name.', [
        'number' => $booking->booking_number,
        'car' => $rental?->car_name,
        'from' => $rental?->rental_start_date?->format('d/m/Y h:i A'),
        'to' => $rental?->rental_end_date?->format('d/m/Y h:i A'),
        'total' => Money::display($booking->amount),
        'name' => $booking->customer_name,
    ]);
@endphp
<x-site.layout :title="__('Reserva') . ' ' . $booking->booking_number">
    <div class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
        <div class="text-center">
            <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-green-100 text-green-600"><x-site.icon name="check" class="size-8" /></span>
            <h1 class="mt-5 text-3xl font-extrabold sm:text-4xl">{{ __('¡Reserva recibida!') }}</h1>
            <p class="mt-3 text-lg text-neutral-600">{!! __('Tu número de reserva es :number. Te contactaremos pronto para confirmarla.', ['number' => '<strong class="text-ink">' . e($booking->booking_number) . '</strong>']) !!}</p>
        </div>

        <div class="mt-10 rounded-2xl border border-black/10 p-6">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div><dt class="text-sm text-neutral-500">{{ __('Vehículo') }}</dt><dd class="font-bold">{{ $rental?->car_name }}</dd></div>
                <div><dt class="text-sm text-neutral-500">{{ __('Cliente') }}</dt><dd class="font-bold">{{ $booking->customer_name }}</dd></div>
                <div><dt class="text-sm text-neutral-500">{{ __('Salida') }}</dt><dd class="font-bold">{{ $rental?->rental_start_date?->format('d/m/Y h:i A') }}</dd></div>
                <div><dt class="text-sm text-neutral-500">{{ __('Devolución') }}</dt><dd class="font-bold">{{ $rental?->rental_end_date?->format('d/m/Y h:i A') }}</dd></div>
                <div><dt class="text-sm text-neutral-500">{{ __('Forma de pago') }}</dt><dd class="font-bold">{{ __($booking->payment?->payment_channel?->getLabel() ?? '') }}</dd></div>
                <div><dt class="text-sm text-neutral-500">{{ __('Total') }}</dt><dd class="text-xl font-extrabold">{{ Money::display($booking->amount) }}</dd></div>
            </dl>

            @if ($instructions)
                <p class="mt-6 rounded-xl bg-neutral-100 p-4 text-sm text-neutral-700">{{ $instructions }}</p>
            @endif
        </div>

        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
            @if ($chat = \App\Support\SiteContent::contactUrl($message))
                <a href="{{ $chat }}" target="_blank" rel="noopener"
                   class="rounded-lg bg-[#25d366] px-6 py-3 text-center font-bold text-white hover:opacity-90">{{ __('Confirmar por WhatsApp') }}</a>
            @endif
            <a href="{{ lroute('site.cars') }}" class="rounded-lg border-2 border-ink px-6 py-3 text-center font-bold hover:bg-ink hover:text-white">{{ __('Ver más vehículos') }}</a>
        </div>
    </div>
</x-site.layout>
