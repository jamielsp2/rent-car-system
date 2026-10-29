@if ($rentals->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">Este vehículo todavía no tiene reservas.</p>
@else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 dark:text-gray-400">
                    <th class="py-2 pr-4 font-medium">Reserva</th>
                    <th class="py-2 pr-4 font-medium">Quién la rentó</th>
                    <th class="py-2 pr-4 font-medium">Desde</th>
                    <th class="py-2 pr-4 font-medium">Hasta</th>
                    <th class="py-2 pr-4 font-medium text-right">Precio de renta</th>
                    <th class="py-2 font-medium">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rentals as $rental)
                    <tr class="border-t border-gray-200 dark:border-gray-700">
                        <td class="py-2 pr-4">{{ $rental->booking?->booking_number }}</td>
                        <td class="py-2 pr-4">{{ $rental->booking?->customer_name }}</td>
                        <td class="py-2 pr-4">{{ $rental->rental_start_date->format('d/m/Y H:i') }}</td>
                        <td class="py-2 pr-4">{{ $rental->rental_end_date->format('d/m/Y H:i') }}</td>
                        <td class="py-2 pr-4 text-right">{{ \App\Support\Money::format($rental->price) }}</td>
                        <td class="py-2">{{ $rental->booking?->status?->getLabel() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
