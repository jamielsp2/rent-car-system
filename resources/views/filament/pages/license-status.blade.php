@php
    $state = $license['state'];
    $info = $license['license'];
    $labels = [
        'active' => ['Activa', 'bg-green-100 text-green-800 ring-green-300 dark:bg-green-950 dark:text-green-200 dark:ring-green-800'],
        'grace' => ['Vencida (en gracia)', 'bg-amber-100 text-amber-900 ring-amber-300 dark:bg-amber-950 dark:text-amber-200 dark:ring-amber-800'],
        'expired' => ['Vencida', 'bg-red-100 text-red-900 ring-red-300 dark:bg-red-950 dark:text-red-200 dark:ring-red-800'],
        'revoked' => ['Suspendida', 'bg-red-100 text-red-900 ring-red-300 dark:bg-red-950 dark:text-red-200 dark:ring-red-800'],
        'wrong_domain' => ['De otro dominio', 'bg-red-100 text-red-900 ring-red-300 dark:bg-red-950 dark:text-red-200 dark:ring-red-800'],
        'invalid' => ['Código no válido', 'bg-red-100 text-red-900 ring-red-300 dark:bg-red-950 dark:text-red-200 dark:ring-red-800'],
        'missing' => ['No activada', 'bg-red-100 text-red-900 ring-red-300 dark:bg-red-950 dark:text-red-200 dark:ring-red-800'],
    ];
    [$label, $badge] = $labels[$state] ?? $labels['missing'];
    $item = 'text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400';
    $value = 'mt-1 text-sm font-medium text-gray-950 dark:text-white';
@endphp

<div class="space-y-5">
    <div class="flex flex-wrap items-center gap-3">
        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-bold ring-1 {{ $badge }}">{{ $label }}</span>
        @if ($notice)
            <span class="text-sm text-gray-600 dark:text-gray-300">{{ $notice['message'] }}</span>
        @elseif ($state === 'active')
            <span class="text-sm text-gray-600 dark:text-gray-300">Todo en orden. No hace falta hacer nada.</span>
        @endif
    </div>

    @if ($info)
        <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="{{ $item }}">Cliente</dt><dd class="{{ $value }}">{{ $info['client'] ?: '—' }}</dd></div>
            <div><dt class="{{ $item }}">Tipo</dt><dd class="{{ $value }}">{{ $info['plan'] === 'annual' ? 'Anual' : ($info['plan'] === 'monthly' ? 'Mensual' : '—') }}</dd></div>
            <div>
                <dt class="{{ $item }}">Vence</dt>
                <dd class="{{ $value }}">
                    {{ date('d/m/Y', $license['expires_at']) }}
                    <span class="font-normal text-gray-500 dark:text-gray-400">
                        @if ($license['days_left'] >= 0) (en {{ $license['days_left'] }} {{ $license['days_left'] === 1 ? 'día' : 'días' }}) @else (hace {{ abs($license['days_left']) }} {{ abs($license['days_left']) === 1 ? 'día' : 'días' }}) @endif
                    </span>
                </dd>
            </div>
            <div><dt class="{{ $item }}">Dominio</dt><dd class="{{ $value }}">{{ $info['domain'] ?: 'Cualquiera' }}</dd></div>
            <div><dt class="{{ $item }}">Código</dt><dd class="{{ $value }} font-mono text-xs">{{ $info['id'] }}</dd></div>
            <div><dt class="{{ $item }}">Última consulta</dt><dd class="{{ $value }}">{{ $checkedAt ? date('d/m/Y H:i', $checkedAt) : 'Aún no se ha consultado' }}</dd></div>
            <div><dt class="{{ $item }}">Almacenamiento</dt><dd class="{{ $value }}">{{ $storage ? 'Cloudflare R2 · ' . $storage : 'En este servidor' }}</dd></div>
        </dl>
    @else
        <p class="text-sm text-gray-600 dark:text-gray-300">Todavía no hay una licencia activada en este sistema. Pega el código en el recuadro de abajo.</p>
    @endif
</div>
