@php($license = \App\Support\License::current())
@if ($license['state'] === 'grace' || ($license['state'] === 'active' && $license['days_left'] <= 7))
    <div class="mb-4 rounded-xl bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900 ring-1 ring-amber-200 dark:bg-amber-950 dark:text-amber-100 dark:ring-amber-800" role="alert">
        @if ($license['state'] === 'grace')
            Tu licencia venció el {{ date('d/m/Y', $license['expires_at']) }}. El sistema se detendrá el {{ date('d/m/Y', $license['grace_ends_at']) }} si no se renueva.
        @else
            Tu licencia vence en {{ max($license['days_left'], 0) }} {{ $license['days_left'] === 1 ? 'día' : 'días' }} ({{ date('d/m/Y', $license['expires_at']) }}).
        @endif
        <a href="{{ route('license.show') }}" class="font-bold underline">Ver licencia</a>
    </div>
@endif
