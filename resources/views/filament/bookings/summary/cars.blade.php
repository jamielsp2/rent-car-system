<x-filament-widgets::widget>
    <x-filament::section class="h-full">
        <x-slot name="heading">Los más pedidos</x-slot>
        <x-slot name="description">Vehículos con más reservas en el período</x-slot>

        @if ($cars->isEmpty())
            <p class="py-8 text-center text-sm text-gray-500">Sin reservas en este período.</p>
        @else
            <ul class="space-y-4">
                @foreach ($cars as $i => $car)
                    <li class="flex items-center gap-3">
                        <div class="relative shrink-0">
                            @if ($car['image'])
                                <img src="{{ $car['image'] }}" alt="" loading="lazy" class="h-11 w-14 rounded-md object-cover ring-1 ring-gray-950/5">
                            @else
                                <span class="block h-11 w-14 rounded-md bg-slate-100"></span>
                            @endif
                            <span @class([
                                'absolute -left-1.5 -top-1.5 flex size-5 items-center justify-center rounded-full text-[10px] font-bold ring-2 ring-white',
                                'bg-amber-400 text-amber-950' => $i === 0,
                                'bg-slate-300 text-slate-800' => $i === 1,
                                'bg-orange-300 text-orange-900' => $i === 2,
                                'bg-slate-100 text-slate-500' => $i > 2,
                            ])>{{ $i + 1 }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between gap-2">
                                @if ($car['url'])
                                    <a href="{{ $car['url'] }}" class="truncate text-sm font-medium text-gray-950 hover:text-primary-600">{{ $car['name'] }}</a>
                                @else
                                    <span class="truncate text-sm font-medium text-gray-950">{{ $car['name'] }}</span>
                                @endif
                                <span class="shrink-0 text-sm font-semibold tabular-nums text-gray-950">{{ $car['bookings'] }}</span>
                            </div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-gradient-to-r from-[#206bc4] to-sky-400" style="width: {{ $car['share'] }}%"></div>
                            </div>
                            <p class="mt-1 text-xs text-gray-400">{{ $car['days'] }} {{ $car['days'] === 1 ? 'día rentado' : 'días rentados' }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
