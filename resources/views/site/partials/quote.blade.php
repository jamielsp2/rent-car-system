@php use App\Support\Money; @endphp
@if ($error)
    <p class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $error }}</p>
@elseif ($quote)
    <div class="space-y-2 text-sm">
        <div class="flex justify-between"><span>{{ Money::display($quote['rate']) }} × {{ $quote['days'] }} {{ $quote['days'] === 1 ? __('día') : __('días') }}</span><span>{{ Money::display($quote['rental']) }}</span></div>
        @foreach ($quote['services'] as $service)
            <div class="flex justify-between text-neutral-600"><span>{{ $service['name'] }}</span><span>{{ Money::display($service['amount']) }}</span></div>
        @endforeach
        @foreach ($quote['taxes'] as $tax)
            <div class="flex justify-between text-neutral-600"><span>{{ $tax['name'] }}</span><span>{{ Money::display($tax['amount']) }}</span></div>
        @endforeach
        <div class="flex justify-between border-t border-black/10 pt-2 text-lg font-extrabold"><span>{{ __('Total') }}</span><span>{{ Money::display($quote['total']) }}</span></div>
        @if ($quote['deposit'] > 0)
            <p class="text-neutral-500">{{ __('+ Depósito de garantía reembolsable: :amount', ['amount' => Money::display($quote['deposit'])]) }}</p>
        @endif
    </div>

    @if (! $quote['meets_min_days'])
        <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">{{ __('Este vehículo se renta por un mínimo de :days días.', ['days' => $quote['min_days']]) }}</p>
    @elseif (! $available)
        <p class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ __('No está disponible en esas fechas. Prueba otras fechas o mira otros vehículos.') }}</p>
    @else
        <a href="{{ lroute('site.checkout', ['key' => $car->slug->key, 'from' => $dates['from'], 'to' => $dates['to'], 'services' => $selected]) }}"
           class="mt-5 block rounded-lg bg-brand py-3 text-center font-bold text-white transition hover:bg-brand-dark">{{ __('Reservar ahora') }}</a>
    @endif
@else
    <p class="text-sm text-neutral-500">{{ __('Elige las fechas para ver el precio total.') }}</p>
@endif
