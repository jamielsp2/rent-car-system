<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Últimas reservas</x-slot>
        <x-slot name="description">Las más recientes que han llegado</x-slot>
        <x-slot name="afterHeader">
            <a href="{{ $allUrl }}" class="inline-flex items-center gap-1 text-sm font-semibold text-primary-600 hover:text-primary-700">
                Ver todas <x-filament::icon icon="heroicon-m-arrow-right" class="size-4" />
            </a>
        </x-slot>

        @if ($bookings->isEmpty())
            <p class="py-8 text-center text-sm text-gray-500">Todavía no hay reservas.</p>
        @else
            <ul class="-my-2 divide-y divide-gray-100">
                @foreach ($bookings as $booking)
                    <li>
                        <a href="{{ $booking['url'] }}" class="group -mx-2 flex items-center gap-4 rounded-lg px-2 py-3 transition hover:bg-slate-50">
                            @if ($booking['image'])
                                <img src="{{ $booking['image'] }}" alt="" loading="lazy" class="h-11 w-16 shrink-0 rounded-md object-cover ring-1 ring-gray-950/5">
                            @else
                                <span class="h-11 w-16 shrink-0 rounded-md bg-slate-100"></span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-gray-950 group-hover:text-primary-600">{{ $booking['customer'] }}</p>
                                <p class="truncate text-xs text-gray-500">
                                    <span class="font-medium text-gray-600">{{ $booking['number'] }}</span>
                                    @if ($booking['car']) · {{ $booking['car'] }} @endif
                                </p>
                            </div>
                            <div class="hidden shrink-0 text-right sm:block">
                                <p class="text-sm font-medium tabular-nums text-gray-700">{{ $booking['dates'] }}</p>
                                @if ($booking['days'])<p class="text-xs text-gray-400">{{ $booking['days'] }} {{ $booking['days'] === 1 ? 'día' : 'días' }}</p>@endif
                            </div>
                            <div class="flex w-40 shrink-0 flex-col items-end gap-1">
                                <x-filament::badge :color="$booking['status']->getColor()" size="sm">{{ $booking['status']->getLabel() }}</x-filament::badge>
                                <span class="text-[11px] text-gray-400">{{ $booking['when'] }}</span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
