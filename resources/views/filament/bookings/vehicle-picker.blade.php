{{-- Vehicle list for the new-booking form: pages of 5, a "Ver fotos" button on each row, and a photo viewer. --}}
@php
    $statePath = $field->getStatePath();
    $vehicles = $field->getVehicles();
    // Rebuild the component when the list changes, so Alpine starts again with the new vehicles.
    $listKey = md5(json_encode($vehicles));
@endphp

<x-dynamic-component :component="$field->getFieldWrapperView()" :field="$field">
    <div
        wire:key="vehicle-picker-{{ $listKey }}"
        x-data="{
            selected: $wire.$entangle('{{ $statePath }}'),
            vehicles: @js($vehicles),
            perPage: 5,
            page: 1,
            photosOpen: false,
            photoCar: null,
            photoIndex: 0,
            get pageCount() { return Math.max(1, Math.ceil(this.vehicles.length / this.perPage)) },
            get pageItems() { const start = (this.page - 1) * this.perPage; return this.vehicles.slice(start, start + this.perPage) },
            goTo(n) { this.page = Math.min(Math.max(1, n), this.pageCount) },
            openPhotos(car) { this.photoCar = car; this.photoIndex = 0; this.photosOpen = true },
            nextPhoto() { this.photoIndex = (this.photoIndex + 1) % this.photoCar.photos.length },
            prevPhoto() { this.photoIndex = (this.photoIndex - 1 + this.photoCar.photos.length) % this.photoCar.photos.length },
        }"
        class="space-y-3"
    >
        <p x-show="vehicles.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
            No hay vehículos disponibles con estos filtros y fechas.
        </p>

        <ul x-show="vehicles.length > 0" class="divide-y divide-gray-200 rounded-lg ring-1 ring-gray-950/5 dark:divide-white/10 dark:ring-white/10">
            <template x-for="car in pageItems" :key="car.id">
                <li class="flex items-center gap-3 p-3" :class="selected == car.id ? 'bg-primary-50 dark:bg-primary-500/10' : ''">
                    <button type="button" class="flex min-w-0 flex-1 items-center gap-3 text-start" @click="selected = car.id">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full ring-1"
                              :class="selected == car.id ? 'ring-2 ring-primary-600' : 'ring-gray-300 dark:ring-white/20'">
                            <span class="h-2.5 w-2.5 rounded-full bg-primary-600" x-show="selected == car.id"></span>
                        </span>
                        <img :src="car.cover" alt="" class="h-12 w-20 shrink-0 rounded object-cover" x-show="car.cover">
                        <span class="min-w-0">
                            <span class="block truncate font-medium text-gray-950 dark:text-white" x-text="car.name"></span>
                            <span class="block truncate text-xs text-gray-500 dark:text-gray-400" x-text="car.make + ' · ' + car.price"></span>
                        </span>
                    </button>

                    <button type="button" x-show="car.photos.length > 0" @click.stop="openPhotos(car)"
                            class="shrink-0 rounded-lg px-2.5 py-1.5 text-xs font-medium ring-1 ring-gray-300 hover:bg-gray-50 dark:ring-white/20 dark:hover:bg-white/5">
                        Ver fotos (<span x-text="car.photos.length"></span>)
                    </button>
                </li>
            </template>
        </ul>

        <div x-show="vehicles.length > perPage" class="flex items-center justify-between gap-2 text-sm">
            <button type="button" @click="goTo(page - 1)" :disabled="page === 1"
                    class="rounded-lg px-3 py-1.5 ring-1 ring-gray-300 disabled:opacity-40 dark:ring-white/20">
                Anterior
            </button>
            <span class="text-gray-500 dark:text-gray-400">
                Página <span x-text="page"></span> de <span x-text="pageCount"></span>
                · <span x-text="vehicles.length"></span> vehículos
            </span>
            <button type="button" @click="goTo(page + 1)" :disabled="page === pageCount"
                    class="rounded-lg px-3 py-1.5 ring-1 ring-gray-300 disabled:opacity-40 dark:ring-white/20">
                Siguiente
            </button>
        </div>

        {{-- Photo viewer --}}
        <div x-show="photosOpen" x-cloak x-transition.opacity
             class="fixed inset-0 flex items-center justify-center bg-black/70 p-4"
             style="z-index: 70;"
             @click.self="photosOpen = false"
             @keydown.arrow-right.window="if (photosOpen && photoCar) nextPhoto()"
             @keydown.arrow-left.window="if (photosOpen && photoCar) prevPhoto()">
            <div class="w-full max-w-3xl overflow-hidden rounded-xl bg-white shadow-xl dark:bg-gray-900" @click.stop>
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-white/10">
                    <span class="font-semibold text-gray-950 dark:text-white" x-text="photoCar ? photoCar.name : ''"></span>
                    <button type="button" @click="photosOpen = false" aria-label="Cerrar"
                            class="rounded-md px-2 py-1 text-gray-500 hover:bg-gray-100 dark:hover:bg-white/10">✕</button>
                </div>

                <template x-if="photoCar">
                    <div class="space-y-3 p-4">
                        <div class="relative overflow-hidden rounded-lg bg-gray-900" style="aspect-ratio: 16 / 10;">
                            <img :src="photoCar.photos[photoIndex]" :alt="photoCar.name" class="absolute inset-0 h-full w-full object-contain">

                            <button type="button" x-show="photoCar.photos.length > 1" @click="prevPhoto()" aria-label="Foto anterior"
                                    class="absolute left-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/55 text-white shadow hover:bg-black/75">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L8.832 10l3.938 3.71a.75.75 0 1 1-1.04 1.08l-4.5-4.25a.75.75 0 0 1 0-1.08l4.5-4.25a.75.75 0 0 1 1.06.02Z" clip-rule="evenodd"/></svg>
                            </button>
                            <button type="button" x-show="photoCar.photos.length > 1" @click="nextPhoto()" aria-label="Foto siguiente"
                                    class="absolute right-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/55 text-white shadow hover:bg-black/75">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                            </button>
                            <span class="absolute bottom-3 right-3 rounded-md bg-black/60 px-2 py-0.5 text-xs font-medium text-white"
                                  x-text="(photoIndex + 1) + ' / ' + photoCar.photos.length"></span>
                        </div>

                        <div x-show="photoCar.photos.length > 1" class="grid grid-cols-6 gap-2 sm:grid-cols-8">
                            <template x-for="(url, i) in photoCar.photos" :key="i">
                                <button type="button" @click="photoIndex = i" :aria-label="'Ver foto ' + (i + 1)"
                                        :class="photoIndex === i ? 'ring-2 ring-primary-500' : 'opacity-70 hover:opacity-100'"
                                        class="overflow-hidden rounded-md transition">
                                    <img :src="url" alt="" class="aspect-square w-full object-cover">
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-dynamic-component>
