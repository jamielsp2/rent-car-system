{{-- Replays the guided tour, from any admin page. --}}
@if (auth()->user()?->canAccessModule('reportes') || auth()->user()?->canAccessModule('reservas') || auth()->user()?->canAccessModule('vehiculos'))
    <button type="button" data-tour-start
       class="me-3 inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-1.5 text-sm font-medium text-white ring-1 ring-white/15 transition hover:bg-white/20"
       title="Ver el recorrido guiado del panel">
        <x-filament::icon icon="heroicon-m-question-mark-circle" class="size-4" />
        <span class="hidden sm:inline">Tour guiado</span>
    </button>
@endif
