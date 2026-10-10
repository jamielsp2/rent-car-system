@php
    $contact = \App\Support\SiteContent::contact();
    $social = \App\Support\SiteContent::social();
    $siteName = \App\Support\SiteContent::name();
    $hero = \App\Support\SiteContent::hero();
    $currencies = \App\Support\Money::currencies();
    $currentCurrency = \App\Support\Money::current();
    $logo = \App\Support\SiteContent::logo();
    $nav = [
        ['label' => __('Inicio'), 'url' => lroute('site.home'), 'active' => request()->routeIs('site.home')],
        ['label' => __('Sobre nosotros'), 'url' => lroute('site.about'), 'active' => request()->routeIs('site.about')],
        ['label' => __('Nuestros vehículos'), 'url' => lroute('site.cars'), 'active' => request()->routeIs('site.cars', 'site.car')],
        ['label' => __('Preguntas frecuentes'), 'url' => lroute('site.faqs'), 'active' => request()->routeIs('site.faqs')],
        ['label' => __('Blog'), 'url' => lroute('site.blog'), 'active' => request()->routeIs('site.blog', 'site.post')],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title . ' - ' . $siteName : $siteName . ' - ' . $hero['title'] }}</title>
    <meta name="description" content="{{ $description ?? $hero['subtitle'] }}">
    @if ($favicon = \App\Support\SiteContent::favicon())
        <link rel="icon" href="{{ $favicon }}">
    @endif
    @fonts
    @vite(['resources/css/app.css'])
    {{-- Brand colour chosen in the admin (Configuración → Web pública). --}}
    <style>:root { --color-brand: {{ \App\Support\SiteContent::brandColor() }}; --color-brand-dark: {{ \App\Support\SiteContent::brandColorDark() }}; }</style>
</head>
<body class="bg-white font-sans text-ink antialiased">
    {{-- Top bar --}}
    <div class="bg-black text-sm text-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-2.5 sm:px-6">
            <div class="flex flex-wrap items-center gap-x-6 gap-y-1">
                @if ($contact['phone'])
                    <a href="tel:{{ $contact['phone_href'] }}" class="flex items-center gap-2 hover:text-brand"><x-site.icon name="phone" class="size-4" /> {{ $contact['phone'] }}</a>
                @endif
                @if ($contact['email'])
                    <a href="mailto:{{ $contact['email'] }}" class="hidden items-center gap-2 hover:text-brand sm:flex"><x-site.icon name="mail" class="size-4" /> {{ $contact['email'] }}</a>
                @endif
            </div>
            <div class="flex items-center gap-6">
                @if ($contact['hours'])
                    <span class="hidden items-center gap-2 text-white/70 md:flex"><x-site.icon name="clock" class="size-4" /> {{ $contact['hours'] }}</span>
                @endif
                @if ($alternates = \App\Support\Lang::alternates() and count($alternates) > 1)
                    <div class="flex items-center gap-1" role="group" aria-label="{{ __('Idioma') }}">
                        @foreach ($alternates as $locale => $url)
                            <a href="{{ $url }}" hreflang="{{ $locale }}" lang="{{ $locale }}" title="{{ \App\Support\Lang::label($locale) }}"
                               @class([
                                   'rounded px-2 py-0.5 text-xs font-bold uppercase transition',
                                   'bg-brand text-white' => $locale === app()->getLocale(),
                                   'text-white/70 hover:text-white' => $locale !== app()->getLocale(),
                               ])>{{ $locale }}</a>
                        @endforeach
                    </div>
                @endif
                @if ($currencies->count() > 1)
                    <div class="flex items-center gap-1" aria-label="{{ __('Moneda') }}">
                        @foreach ($currencies as $currency)
                            <a href="{{ lroute('site.currency', $currency->title) }}" rel="nofollow"
                               @class([
                                   'rounded px-2 py-0.5 text-xs font-bold transition',
                                   'bg-brand text-white' => $currency->is($currentCurrency),
                                   'text-white/70 hover:text-white' => ! $currency->is($currentCurrency),
                               ])>{{ $currency->title }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Header --}}
    <header class="sticky top-0 z-40 border-b border-black/5 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-3 sm:px-6">
            <a href="{{ lroute('site.home') }}" class="shrink-0" aria-label="{{ $siteName }}">
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $siteName }}" class="h-14 w-auto">
                @else
                    <span class="text-2xl font-extrabold">{{ $siteName }}</span>
                @endif
            </a>

            <nav class="hidden items-center gap-5 md:flex lg:gap-8">
                @foreach ($nav as $item)
                    <a href="{{ $item['url'] }}" @class(['text-[15px] font-medium transition hover:text-brand', 'text-brand' => $item['active']])>{{ $item['label'] }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-3">
                <a href="{{ lroute('site.cars') }}" class="rounded-lg bg-brand px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-dark">{{ __('Reservar') }}</a>
                <details class="relative md:hidden">
                    <summary class="flex cursor-pointer list-none items-center rounded-lg border border-black/10 p-2" aria-label="{{ __('Menú') }}"><x-site.icon name="menu" /></summary>
                    <div class="absolute right-0 mt-2 w-60 rounded-xl border border-black/5 bg-white p-2 shadow-xl">
                        @foreach ($nav as $item)
                            <a href="{{ $item['url'] }}" class="block rounded-lg px-3 py-2 font-medium hover:bg-neutral-100">{{ $item['label'] }}</a>
                        @endforeach
                    </div>
                </details>
            </div>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="bg-black text-white/70">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 md:grid-cols-2 lg:grid-cols-4">
            <div class="space-y-4 lg:col-span-2">
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $siteName }}" class="h-16 w-auto">
                @else
                    <p class="text-2xl font-extrabold text-white">{{ $siteName }}</p>
                @endif
                @if ($contact['address'])
                    <p class="flex gap-2"><x-site.icon name="map" class="mt-0.5 size-4" /> {{ $contact['address'] }}</p>
                @endif
                @if ($contact['hours'])
                    <p class="flex gap-2"><x-site.icon name="clock" class="mt-0.5 size-4" /> {{ $contact['hours'] }}</p>
                @endif
                @if ($contact['email'])
                    <p class="flex gap-2"><x-site.icon name="mail" class="mt-0.5 size-4" /> <a href="mailto:{{ $contact['email'] }}" class="hover:text-white">{{ $contact['email'] }}</a></p>
                @endif
                @if ($contact['phone'])
                    <div class="pt-2">
                        <p class="flex items-center gap-2 text-white"><x-site.icon name="phone" class="size-4" /> {{ __('¿Necesitas ayuda?') }}</p>
                        <a href="tel:{{ $contact['phone_href'] }}" class="text-2xl font-bold text-brand">{{ $contact['phone'] }}</a>
                    </div>
                @endif
            </div>
            <div>
                <h3 class="mb-4 text-lg font-semibold text-white">{{ __('Nuestra empresa') }}</h3>
                <ul class="space-y-3">
                    <li><a href="{{ lroute('site.about') }}" class="hover:text-white">{{ __('Sobre nosotros') }}</a></li>
                    <li><a href="{{ lroute('site.cars') }}" class="hover:text-white">{{ __('Nuestros vehículos') }}</a></li>
                    <li><a href="{{ lroute('site.faqs') }}" class="hover:text-white">{{ __('Preguntas frecuentes') }}</a></li>
                </ul>
            </div>
            <div>
                <h3 class="mb-4 text-lg font-semibold text-white">{{ __('Soporte') }}</h3>
                <ul class="space-y-3">
                    @if ($contact['whatsapp'])
                        <li><a href="https://wa.me/{{ $contact['whatsapp'] }}" target="_blank" rel="noopener" class="hover:text-white">WhatsApp</a></li>
                    @endif
                    @if ($contact['phone'])
                        <li><a href="tel:{{ $contact['phone_href'] }}" class="hover:text-white">{{ __('Llámanos') }}</a></li>
                    @endif
                    @if ($contact['email'])
                        <li><a href="mailto:{{ $contact['email'] }}" class="hover:text-white">{{ __('Escríbenos') }}</a></li>
                    @endif
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-6 text-sm sm:px-6">
                <p>&copy; {{ date('Y') }} {{ \App\Support\SiteContent::copyright() }}</p>
                <div class="flex gap-4">
                    @if ($social['facebook'])
                        <a href="{{ $social['facebook'] }}" target="_blank" rel="noopener" class="hover:text-white">Facebook</a>
                    @endif
                    @if ($social['instagram'])
                        <a href="{{ $social['instagram'] }}" target="_blank" rel="noopener" class="hover:text-white">Instagram</a>
                    @endif
                </div>
            </div>
        </div>
    </footer>

    @if ($contact['whatsapp'])
        {{-- WhatsApp --}}
        <a href="https://wa.me/{{ $contact['whatsapp'] }}" target="_blank" rel="noopener" aria-label="WhatsApp"
           class="fixed right-5 bottom-5 z-50 flex size-14 items-center justify-center rounded-full bg-[#25d366] text-white shadow-lg transition hover:scale-105">
            <svg class="size-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.39-1.48-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.6-.92-2.2-.24-.58-.49-.5-.67-.5h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.87 1.21 3.07.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.08 1.75-.72 2-1.41.25-.69.25-1.28.17-1.41-.07-.13-.27-.2-.57-.35ZM12.04 21.5h-.01a9.45 9.45 0 0 1-4.82-1.32l-.35-.2-3.58.94.96-3.49-.23-.36a9.43 9.43 0 0 1-1.45-5.03C2.56 6.83 6.8 2.6 12.05 2.6c2.54 0 4.92.99 6.72 2.78a9.42 9.42 0 0 1 2.78 6.71c0 5.23-4.25 9.41-9.5 9.41Zm8.07-17.48A11.3 11.3 0 0 0 12.04.7C5.8.7.72 5.78.72 12.02c0 2 .52 3.94 1.52 5.66L.62 23.3l5.76-1.51a11.3 11.3 0 0 0 5.66 1.44h.01c6.24 0 11.32-5.08 11.32-11.32 0-3.03-1.18-5.87-3.26-8Z"/></svg>
        </a>
    @endif
</body>
</html>
