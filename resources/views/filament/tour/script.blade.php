{{-- Guided tour of Informes, Reservas and Vehículos (resources/js/admin-tour.js). --}}
@auth
    @php
        $user = auth()->user();
        $sections = collect([
            'informes' => 'reportes',
            'reservas' => 'reservas',
            'vehiculos' => 'vehiculos',
        ])->filter(fn (string $module) => $user->canAccessModule($module))->keys()->values();

        $tour = [
            'user' => $user->getKey(),
            'sections' => $sections,
            'urls' => [
                'informes' => \App\Filament\Pages\Reports::getUrl(),
                'reservas' => \App\Filament\Clusters\Reservas\Pages\BookingDashboard::getUrl(),
                'vehiculos' => \App\Filament\Resources\Cars\CarResource::getUrl('index'),
            ],
        ];
    @endphp
    @if ($sections->isNotEmpty())
        <script type="application/json" id="admin-tour-config">@json($tour)</script>
        @vite('resources/js/admin-tour.js')
    @endif
@endauth
