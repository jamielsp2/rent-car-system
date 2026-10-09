<x-filament-panels::page>
    <x-filament::section>
        <div class="flex flex-wrap items-end justify-between gap-4" wire:ignore>
            <div class="flex flex-wrap items-end gap-4">
                <label class="block text-sm">
                    <span class="mb-1 block font-medium text-gray-700">Vehículo</span>
                    <select id="calendar-car" class="rounded-lg border-gray-300 text-sm shadow-sm">
                        <option value="">Todos los vehículos</option>
                        @foreach ($cars as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="flex items-center gap-2 pb-2 text-sm text-gray-700">
                    <input id="calendar-cancelled" type="checkbox" class="rounded border-gray-300"> Mostrar canceladas
                </label>
            </div>
            <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-600">
                @foreach ($statuses as $label => $color)
                    <span class="flex items-center gap-1.5"><span class="inline-block size-3 rounded-sm" style="background: {{ $color }}"></span>{{ $label }}</span>
                @endforeach
            </div>
        </div>

        <div class="mt-6" wire:ignore>
            <div id="booking-calendar" data-feed="{{ route('admin.bookings.calendar.events') }}"></div>
        </div>
    </x-filament::section>

    @vite('resources/js/admin-booking-calendar.js')
</x-filament-panels::page>
