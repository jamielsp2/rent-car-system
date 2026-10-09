{{-- Opens the public site in a new tab, from any admin page. --}}
<a href="{{ route('site.home') }}" target="_blank" rel="noopener"
   class="me-3 inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-1.5 text-sm font-medium text-white ring-1 ring-white/15 transition hover:bg-white/20"
   title="Abrir la web en una pestaña nueva">
    <x-filament::icon icon="heroicon-m-globe-alt" class="size-4" />
    <span class="hidden sm:inline">Ver la web</span>
    <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="hidden size-3.5 opacity-70 sm:inline" />
</a>
