@php
    $state = $license['state'];
    $info = $license['license'];
    $valid = in_array($state, ['active', 'grace'], true);
    $messages = [
        'missing' => ['Tenant no activado', 'Este sistema todavía no tiene licencia. Pega el código que te entregó tu proveedor para activarlo.'],
        'invalid' => ['Tenant no activado', 'El código guardado no es válido. Pega de nuevo el código completo.'],
        'active' => ['Licencia activa', 'Tu sistema está activo.'],
        'grace' => ['Licencia vencida', 'Tu licencia venció el ' . date('d/m/Y', $license['expires_at']) . '. Renueva con tu proveedor y pega aquí el código nuevo.'],
        'expired' => ['Licencia vencida', 'Tu licencia venció el ' . date('d/m/Y', $license['expires_at']) . '. Renueva con tu proveedor: si ya pagaste, pulsa «Comprobar ahora» o pega el código renovado.'],
        'revoked' => ['Tenant no activado', 'La licencia fue suspendida o ya no existe. Contacta a tu proveedor y pega aquí el código nuevo.'],
        'wrong_domain' => ['Tenant no activado', 'Esta licencia fue emitida para ' . ($info['domain'] ?? '') . ' y no es válida en este sitio.'],
    ];
    [$title, $text] = $messages[$state] ?? $messages['invalid'];
    $field = 'w-full rounded-xl border border-black/10 bg-white px-3.5 py-3 font-mono text-sm focus:border-brand focus:ring-brand';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Licencia - Rent Car System</title>
    @vite('resources/css/app.css')
</head>
<body class="min-h-dvh bg-neutral-100 font-sans text-neutral-900 antialiased">
    <main class="mx-auto flex min-h-dvh max-w-xl flex-col justify-center px-4 py-10">
        <div class="overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5">
            <div class="relative overflow-hidden bg-black px-7 py-8 text-white">
                <div class="pointer-events-none absolute -right-16 -bottom-24 size-64 rounded-full bg-brand/40 blur-3xl"></div>
                <p class="text-xs font-bold tracking-[0.2em] text-white/60 uppercase">Licencia</p>
                <h1 class="mt-2 text-3xl font-extrabold">{{ $title }}</h1>
                <p class="mt-2 text-white/70">{{ $text }}</p>
            </div>

            <div class="space-y-6 p-7">
                @if (session('status'))
                    <div class="rounded-xl bg-green-50 p-4 text-sm font-semibold text-green-700" role="status">{{ session('status') }}</div>
                @endif
                @if (session('warning'))
                    <div class="rounded-xl bg-amber-50 p-4 text-sm font-semibold text-amber-800" role="alert">{{ session('warning') }}</div>
                @endif
                @if ($errors->any())
                    <div class="rounded-xl bg-red-50 p-4 text-sm text-red-700" role="alert">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                @if ($info)
                    <dl class="grid grid-cols-2 gap-4 rounded-2xl bg-neutral-100 p-5 text-sm">
                        <div><dt class="text-xs font-bold tracking-wide text-neutral-500 uppercase">Cliente</dt><dd class="mt-0.5 font-semibold">{{ $info['client'] }}</dd></div>
                        <div><dt class="text-xs font-bold tracking-wide text-neutral-500 uppercase">Tipo</dt><dd class="mt-0.5 font-semibold">{{ $info['plan'] === 'annual' ? 'Anual' : 'Mensual' }}</dd></div>
                        <div><dt class="text-xs font-bold tracking-wide text-neutral-500 uppercase">Vence</dt><dd class="mt-0.5 font-semibold">{{ date('d/m/Y', $license['expires_at']) }}@if ($state === 'active') <span class="font-normal text-neutral-500">({{ max($license['days_left'], 0) }} días)</span>@endif</dd></div>
                        <div><dt class="text-xs font-bold tracking-wide text-neutral-500 uppercase">Código</dt><dd class="mt-0.5 font-mono text-xs font-semibold">{{ $info['id'] }}</dd></div>
                    </dl>
                @endif

                <form method="POST" action="{{ route('license.store') }}" class="space-y-3">
                    @csrf
                    <label class="block">
                        <span class="mb-1 block text-sm font-semibold">{{ $valid ? 'Pegar un código renovado' : 'Código de licencia' }}</span>
                        <textarea name="code" rows="4" required placeholder="RCS1.eyJ2Ijox…" class="{{ $field }}">{{ old('code') }}</textarea>
                    </label>
                    <button class="w-full rounded-xl bg-brand py-3.5 text-base font-bold text-white transition hover:bg-brand-dark">Activar licencia</button>
                </form>

                <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                    @if ($info && $info['url'])
                        <form method="POST" action="{{ route('license.check') }}">
                            @csrf
                            <button class="font-semibold text-neutral-600 underline underline-offset-4 hover:text-neutral-900">Comprobar ahora</button>
                        </form>
                    @endif
                    <a href="{{ url('/admin') }}" class="font-semibold text-brand underline underline-offset-4">Ir al panel →</a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
