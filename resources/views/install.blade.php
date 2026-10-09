@php($field = 'w-full rounded-xl border border-black/10 bg-white px-3.5 py-3 text-base focus:border-brand focus:ring-brand sm:text-sm')
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Instalación - Rent Car System</title>
    @vite('resources/css/app.css')
</head>
<body class="min-h-dvh bg-neutral-100 font-sans text-neutral-900 antialiased">
    <main class="mx-auto flex min-h-dvh max-w-xl flex-col justify-center px-4 py-10">
        <div class="overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5">
            <div class="relative overflow-hidden bg-black px-7 py-8 text-white">
                <div class="pointer-events-none absolute -right-16 -bottom-24 size-64 rounded-full bg-brand/40 blur-3xl"></div>
                <p class="text-xs font-bold tracking-[0.2em] text-white/60 uppercase">Instalación</p>
                <h1 class="mt-2 text-3xl font-extrabold">Pon en marcha tu sistema</h1>
                <p class="mt-2 text-white/70">Tres datos y listo: tu negocio, la base de datos MySQL y tu usuario administrador. Solo se hace una vez.</p>
            </div>

            <form method="POST" action="{{ route('install.store') }}" class="space-y-7 p-7">
                @csrf

                @if ($errors->any())
                    <div class="rounded-xl bg-red-50 p-4 text-sm text-red-700" role="alert">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <fieldset class="space-y-4">
                    <legend class="mb-1 text-sm font-bold tracking-wide uppercase">1. Tu negocio</legend>
                    <label class="block">
                        <span class="mb-1 block text-sm font-semibold">Nombre del negocio</span>
                        <input name="site_name" value="{{ old('site_name') }}" required maxlength="120" placeholder="Mi Rent Car" class="{{ $field }}">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-sm font-semibold">Dirección del sitio</span>
                        <input type="url" name="app_url" value="{{ old('app_url', $url) }}" required class="{{ $field }}">
                        <span class="mt-1 block text-xs text-neutral-500">Con https://, por ejemplo https://tudominio.com</span>
                    </label>
                </fieldset>

                <fieldset class="space-y-4">
                    <legend class="mb-1 text-sm font-bold tracking-wide uppercase">2. Base de datos MySQL</legend>
                    <p class="text-sm text-neutral-500">Créala vacía en tu hosting; el sistema crea las tablas solo.</p>
                    <div class="grid grid-cols-[1fr_7rem] gap-3">
                        <label class="block">
                            <span class="mb-1 block text-sm font-semibold">Servidor (host)</span>
                            <input name="host" value="{{ old('host', 'localhost') }}" required class="{{ $field }}">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-sm font-semibold">Puerto</span>
                            <input name="port" inputmode="numeric" value="{{ old('port', '3306') }}" required class="{{ $field }}">
                        </label>
                    </div>
                    <label class="block">
                        <span class="mb-1 block text-sm font-semibold">Nombre de la base de datos</span>
                        <input name="database" value="{{ old('database') }}" required autocomplete="off" placeholder="u123456789_rentcar" class="{{ $field }}">
                    </label>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-1 block text-sm font-semibold">Usuario</span>
                            <input name="username" value="{{ old('username') }}" required autocomplete="off" class="{{ $field }}">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-sm font-semibold">Contraseña</span>
                            <input type="password" name="password" autocomplete="new-password" class="{{ $field }}">
                        </label>
                    </div>
                </fieldset>

                <fieldset class="space-y-4">
                    <legend class="mb-1 text-sm font-bold tracking-wide uppercase">3. Administrador</legend>
                    <label class="block">
                        <span class="mb-1 block text-sm font-semibold">Nombre</span>
                        <input name="admin_name" value="{{ old('admin_name') }}" required maxlength="120" autocomplete="name" class="{{ $field }}">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-sm font-semibold">Correo electrónico</span>
                        <input type="email" name="admin_email" value="{{ old('admin_email') }}" required autocomplete="email" class="{{ $field }}">
                    </label>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-1 block text-sm font-semibold">Contraseña</span>
                            <input type="password" name="admin_password" required minlength="8" autocomplete="new-password" class="{{ $field }}">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-sm font-semibold">Repite la contraseña</span>
                            <input type="password" name="admin_password_confirmation" required minlength="8" autocomplete="new-password" class="{{ $field }}">
                        </label>
                    </div>
                </fieldset>

                <label class="flex cursor-pointer gap-3 rounded-xl bg-neutral-100 p-4 text-sm text-neutral-700">
                    <input type="checkbox" name="demo" value="1" @checked(old('demo')) class="mt-0.5 size-5 shrink-0 accent-brand">
                    <span><strong>Cargar datos de ejemplo</strong> (vehículos, reservas y artículos de prueba). Útil para conocer el sistema; no lo marques en un negocio real.</span>
                </label>

                <button class="w-full rounded-xl bg-brand py-4 text-base font-bold text-white transition hover:bg-brand-dark" onclick="this.disabled = true; this.textContent = 'Instalando… puede tardar un minuto'; this.form.submit()">
                    Instalar
                </button>
            </form>
        </div>
        <p class="mt-6 text-center text-xs text-neutral-500">Después podrás cambiar la base de datos en Configuración → Base de datos.</p>
    </main>
</body>
</html>
