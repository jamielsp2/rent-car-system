{{-- Opens the "Nueva reserva" form from any admin page (the list page opens it on arrival). --}}
<a href="{{ route('filament.admin.reservas.resources.bookings.index', ['nueva' => 1]) }}"
   class="me-3 inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-1.5 text-sm font-medium text-white ring-1 ring-white/15 transition hover:bg-white/20"
   title="Crear una reserva nueva">
    <x-filament::icon icon="heroicon-m-plus" class="size-4" />
    <span class="hidden sm:inline">Nueva reserva</span>
</a>
