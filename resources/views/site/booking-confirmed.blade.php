@php
    use App\Support\Money;

    $rental = $booking->rental;
    $message = "Hola, acabo de hacer la reserva {$booking->booking_number} del {$rental?->car_name} "
        . "({$rental?->rental_start_date?->format('d/m/Y h:i A')} - {$rental?->rental_end_date?->format('d/m/Y h:i A')}). "
        . "Total: " . Money::display($booking->amount) . ". Nombre: {$booking->customer_name}.";
@endphp
<x-site.layout :title="'Reserva ' . $booking->booking_number">
    <div class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
        <div class="text-center">
            <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-green-100 text-green-600"><x-site.icon name="check" class="size-8" /></span>
            <h1 class="mt-5 text-3xl font-extrabold sm:text-4xl">¡Reserva recibida!</h1>
            <p class="mt-3 text-lg text-neutral-600">Tu número de reserva es <strong class="text-ink">{{ $booking->booking_number }}</strong>. Te contactaremos pronto para confirmarla.</p>
        </div>

        <div class="mt-10 rounded-2xl border border-black/10 p-6">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div><dt class="text-sm text-neutral-500">Vehículo</dt><dd class="font-bold">{{ $rental?->car_name }}</dd></div>
                <div><dt class="text-sm text-neutral-500">Cliente</dt><dd class="font-bold">{{ $booking->customer_name }}</dd></div>
                <div><dt class="text-sm text-neutral-500">Salida</dt><dd class="font-bold">{{ $rental?->rental_start_date?->format('d/m/Y h:i A') }}</dd></div>
                <div><dt class="text-sm text-neutral-500">Devolución</dt><dd class="font-bold">{{ $rental?->rental_end_date?->format('d/m/Y h:i A') }}</dd></div>
                <div><dt class="text-sm text-neutral-500">Forma de pago</dt><dd class="font-bold">{{ $booking->payment?->payment_channel?->getLabel() }}</dd></div>
                <div><dt class="text-sm text-neutral-500">Total</dt><dd class="text-xl font-extrabold">{{ Money::display($booking->amount) }}</dd></div>
            </dl>

            @if ($instructions)
                <p class="mt-6 rounded-xl bg-neutral-100 p-4 text-sm text-neutral-700">{{ $instructions }}</p>
            @endif
        </div>

        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
            @if ($chat = \App\Support\SiteContent::contactUrl($message))
                <a href="{{ $chat }}" target="_blank" rel="noopener"
                   class="rounded-lg bg-[#25d366] px-6 py-3 text-center font-bold text-white hover:opacity-90">Confirmar por WhatsApp</a>
            @endif
            <a href="{{ route('site.cars') }}" class="rounded-lg border-2 border-ink px-6 py-3 text-center font-bold hover:bg-ink hover:text-white">Ver más vehículos</a>
        </div>
    </div>
</x-site.layout>
