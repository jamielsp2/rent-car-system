@php($notice = \App\Support\License::notice())
@if ($notice)
    <div @class([
        'mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl px-4 py-3 text-sm font-semibold ring-1',
        'bg-red-50 text-red-900 ring-red-300 dark:bg-red-950 dark:text-red-100 dark:ring-red-800' => $notice['level'] === 'danger',
        'bg-amber-50 text-amber-900 ring-amber-200 dark:bg-amber-950 dark:text-amber-100 dark:ring-amber-800' => $notice['level'] === 'warning',
    ]) role="alert">
        <span>{{ $notice['message'] }}</span>
        <a href="{{ \App\Filament\Pages\LicensePage::getUrl() }}" class="shrink-0 rounded-lg bg-black/80 px-3 py-1.5 text-white no-underline hover:bg-black dark:bg-white/90 dark:text-black">Ver licencia</a>
    </div>
@endif
