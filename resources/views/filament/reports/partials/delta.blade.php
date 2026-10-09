{{-- Change against the previous period. Expects $delta (or null) and optional $dark. --}}
@php($dark ??= false)

@if ($delta)
    <span @class([
        'inline-flex items-center gap-0.5 rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums whitespace-nowrap',
        'bg-white/15 text-white' => $dark,
        'bg-gray-100 text-gray-600' => ! $dark && $delta['flat'],
        'bg-emerald-50 text-emerald-700' => ! $dark && ! $delta['flat'] && $delta['good'],
        'bg-rose-50 text-rose-700' => ! $dark && ! $delta['flat'] && ! $delta['good'],
    ])>
        @unless ($delta['flat'] || $delta['text'] === 'Nuevo')
            <svg viewBox="0 0 12 12" @class(['size-3', 'rotate-180' => str_starts_with($delta['text'], '-')]) fill="currentColor" aria-hidden="true"><path d="M6 2.5 10 8H2z"/></svg>
        @endunless
        {{ $delta['text'] }}
    </span>
@endif
