<script setup>
import { computed } from 'vue';
import CharacterCard from '../components/CharacterCard.vue';
import CharacterFilters from '../components/CharacterFilters.vue';
import CharacterPagination from '../components/CharacterPagination.vue';
import { useCharacterCatalog } from '../composables/useCharacterCatalog';
import FavoriteButton from '../../favorites/components/FavoriteButton.vue';
import FavoriteNotice from '../../favorites/components/FavoriteNotice.vue';

const { criteria, result, loading, error, applyFilters, changePage, retry } = useCharacterCatalog();
const errorDetails = computed(() => Object.values(error.value?.details ?? {}).flat().filter((item) => typeof item === 'string'));
const announcement = computed(() => loading.value ? 'Cargando personajes…'
    : error.value ? '' : `${result.value?.meta.total ?? 0} personajes encontrados.`);
</script>

<template>
    <section aria-labelledby="page-title" class="w-full">
        <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-brand-700">Rick and Morty · Catálogo</p>
        <h1 id="page-title" class="text-3xl font-semibold tracking-tight sm:text-5xl">Explora el multiverso.</h1>
        <p class="mt-4 max-w-2xl text-base leading-relaxed text-muted">Encuentra personajes del catálogo sincronizado. Filtra por nombre, estado, especie o género y comparte tu búsqueda desde la URL.</p>
        <CharacterFilters :criteria="criteria" @apply="applyFilters" />
        <p role="status" aria-live="polite" aria-atomic="true" class="mt-6 text-sm font-medium text-muted">{{ announcement }}</p>
        <FavoriteNotice />
        <div id="character-results" :aria-busy="loading" class="mt-4">
            <div v-if="loading" aria-hidden="true" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="index in 3" :key="index" class="h-80 rounded-2xl border border-line bg-brand-100 motion-safe:animate-pulse" />
            </div>
            <div v-else-if="error" role="alert" class="rounded-2xl border border-red-300 bg-red-50 p-6 text-red-900">
                <h2 class="text-lg font-semibold">No se han podido cargar los personajes</h2>
                <p class="mt-2">{{ error.message }}</p>
                <ul v-if="errorDetails.length" class="mt-2 list-disc pl-5"><li v-for="(detail, index) in errorDetails" :key="index">{{ detail }}</li></ul>
                <button type="button" class="mt-4 min-h-12 rounded-lg border border-red-300 px-5 py-2 font-semibold hover:bg-red-100" @click="retry">Reintentar</button>
            </div>
            <template v-else-if="result">
                <ul v-if="result.data.length" aria-label="Personajes encontrados" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <li v-for="character in result.data" :key="character.id" class="min-w-0">
                        <CharacterCard :character="character" :catalog-query="criteria">
                            <template #actions><FavoriteButton :character="character" /></template>
                        </CharacterCard>
                    </li>
                </ul>
                <div v-else class="rounded-2xl border border-line bg-white p-8 text-center">
                    <h2 class="text-xl font-semibold">{{ criteria.page > result.meta.last_page ? 'Esta página no tiene resultados' : 'No hay personajes para esta búsqueda' }}</h2>
                    <p class="mt-3 text-muted">{{ criteria.page > result.meta.last_page ? 'Vuelve a la primera página para consultar los resultados disponibles.' : 'Prueba otros filtros. Si no has aplicado ninguno, puede que el catálogo todavía no esté sincronizado.' }}</p>
                    <button v-if="criteria.page > 1" type="button" class="mt-4 min-h-12 rounded-lg border border-line px-5 py-2 font-medium hover:bg-brand-100" @click="changePage(1)">Volver a la primera página</button>
                </div>
                <CharacterPagination :meta="result.meta" :links="result.links" @page="changePage" />
            </template>
        </div>
    </section>
</template>
