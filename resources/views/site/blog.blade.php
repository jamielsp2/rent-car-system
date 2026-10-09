<x-site.layout title="Blog">
    <x-site.page-header title="Blog" subtitle="Rutas, consejos y experiencias para tu próximo viaje." />

    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6">
        @if ($posts->isEmpty())
            <p class="text-neutral-600">Aún no hay artículos publicados.</p>
        @else
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <x-site.post-card :post="$post" />
                @endforeach
            </div>
            <div class="mt-10">{{ $posts->links() }}</div>
        @endif
    </div>
</x-site.layout>
