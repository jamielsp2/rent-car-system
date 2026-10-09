<x-site.layout title="Preguntas frecuentes">
    <x-site.page-header
        title="Preguntas frecuentes"
        eyebrow="Centro de ayuda"
        subtitle="Requisitos, pagos, seguros, entregas y devoluciones: todo lo que necesitas saber antes de rentar."
        :image="\App\Support\SiteContent::about()['stats'][1]['image']"
    >
        <div class="flex max-w-3xl flex-col gap-4 sm:flex-row sm:items-center">
            <label class="relative flex-1">
                <span class="sr-only">Buscar una pregunta</span>
                <x-site.icon name="search" class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-neutral-400" />
                <input id="faq-search" type="search" placeholder="Busca tu pregunta: depósito, licencia, aeropuerto..." autocomplete="off"
                    class="w-full rounded-xl border-0 bg-white py-4 pr-4 pl-12 text-neutral-900 shadow-2xl ring-1 ring-black/5 placeholder:text-neutral-400 focus:ring-2 focus:ring-brand">
            </label>
            @if ($chat = \App\Support\SiteContent::contactUrl())
                <a href="{{ $chat }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#25d366] px-6 py-4 font-bold text-white transition hover:opacity-90">
                    <x-site.icon name="phone" class="size-5" /> Pregúntanos
                </a>
            @endif
        </div>
        <p class="mt-4 text-sm text-white/60">{{ $faqs->count() }} {{ $faqs->count() === 1 ? 'respuesta' : 'respuestas' }} · ¿No ves la tuya? Escríbenos</p>
    </x-site.page-header>

    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1fr_320px]">
        <div class="space-y-4">
            @forelse ($faqs as $faq)
                <details data-faq class="group rounded-2xl border border-black/10 p-6 open:shadow-md" @if ($loop->first) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-bold">
                        {{ $faq->question }}
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-xl transition group-open:rotate-45 group-open:bg-brand group-open:text-white">+</span>
                    </summary>
                    <div class="mt-4 space-y-3 leading-relaxed text-neutral-700 [&_li]:ml-5 [&_ol]:list-decimal [&_ul]:list-disc">{!! $faq->safe_answer !!}</div>
                </details>
            @empty
                <p class="text-neutral-600">Aún no hay preguntas publicadas.</p>
            @endforelse
            <p id="faq-empty" class="hidden rounded-2xl bg-neutral-100 p-6 text-neutral-600">
                No encontramos esa pregunta. Escríbenos y te respondemos al momento.
            </p>
        </div>

        <aside class="self-start rounded-2xl bg-black p-7 text-white">
            <h2 class="text-2xl font-extrabold">¿No encuentras tu respuesta?</h2>
            <p class="mt-2 text-white/70">Escríbenos y te ayudamos al momento.</p>
            @if ($chat = \App\Support\SiteContent::contactUrl())
                <a href="{{ $chat }}" target="_blank" rel="noopener" class="mt-6 block rounded-lg bg-[#25d366] py-3 text-center font-bold hover:opacity-90">Contáctanos</a>
            @endif
            @if ($phone = \App\Support\SiteContent::contact()['phone'])
                <a href="tel:{{ \App\Support\SiteContent::contact()['phone_href'] }}" class="mt-3 block rounded-lg border border-white/20 py-3 text-center font-bold hover:bg-white/10">{{ $phone }}</a>
            @endif
        </aside>
    </div>

    <script>
        // Narrow the list while typing; matching answers open.
        (function () {
            const input = document.getElementById('faq-search');
            const items = [...document.querySelectorAll('[data-faq]')];
            const empty = document.getElementById('faq-empty');
            const plain = (text) => text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

            input?.addEventListener('input', () => {
                const words = plain(input.value).split(/\s+/).filter(Boolean);
                let shown = 0;

                items.forEach((item, index) => {
                    const text = plain(item.textContent);
                    const match = words.every((word) => text.includes(word));
                    item.hidden = ! match;
                    item.open = words.length ? match : index === 0;
                    shown += match;
                });

                empty.classList.toggle('hidden', shown > 0);
            });
        })();
    </script>
</x-site.layout>
