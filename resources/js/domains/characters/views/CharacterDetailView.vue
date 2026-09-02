<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useCharacterDetail } from '../composables/useCharacterDetail';
import { readCharacterQuery, writeCharacterQuery } from '../services/characterQuery';
import CharacterProfile from '../components/CharacterProfile.vue';
import CharacterLocation from '../components/CharacterLocation.vue';
import CharacterEpisodes from '../components/CharacterEpisodes.vue';
import FavoriteButton from '../../favorites/components/FavoriteButton.vue';
import FavoriteNotice from '../../favorites/components/FavoriteNotice.vue';

const props = defineProps({ externalId: { type: String, required: true } });
const route = useRoute();
const { character, loading, error, retry } = useCharacterDetail(() => props.externalId);
const backToCatalog = computed(() => ({ name: 'characters', query: writeCharacterQuery(readCharacterQuery(route.query)) }));
const notFound = computed(() => error.value?.status === 404);
const heading = computed(() => character.value?.name ?? (notFound.value ? 'Personaje no encontrado' : `Personaje #${props.externalId}`));
</script>

<template>
    <section aria-labelledby="page-title" class="w-full">
        <RouterLink :to="backToCatalog" class="mb-6 inline-flex min-h-11 items-center rounded-lg font-medium text-brand-700 underline underline-offset-4">Volver a personajes</RouterLink>
        <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-brand-700">Rick and Morty · Ficha del personaje</p>
        <h1 id="page-title" class="text-3xl font-semibold tracking-tight break-words sm:text-5xl">{{ heading }}</h1>
        <p role="status" aria-live="polite" aria-atomic="true" class="mt-4 text-sm text-muted">{{ loading ? 'Cargando ficha del personaje…' : character ? 'Ficha cargada.' : '' }}</p>
        <FavoriteNotice />
        <div :aria-busy="loading" class="mt-6">
            <div v-if="loading" aria-hidden="true" class="h-80 rounded-2xl border border-line bg-brand-100 motion-safe:animate-pulse" />
            <div v-else-if="notFound" role="alert" class="rounded-2xl border border-line bg-white p-6">
                <p>El personaje solicitado no existe en el catálogo sincronizado. Puedes volver al listado y elegir otro.</p>
            </div>
            <div v-else-if="error" role="alert" class="rounded-2xl border border-red-300 bg-red-50 p-6 text-red-900">
                <h2 class="text-lg font-semibold">No se ha podido cargar la ficha</h2>
                <p class="mt-2">{{ error.message }}</p>
                <button type="button" class="mt-4 min-h-12 rounded-lg border border-red-300 px-5 py-2 font-semibold hover:bg-red-100" @click="retry">Reintentar</button>
            </div>
            <template v-else-if="character">
                <CharacterProfile :character="character">
                    <template #actions><FavoriteButton :character="character" /></template>
                </CharacterProfile>
                <div class="mt-8 grid gap-6 md:grid-cols-2">
                    <CharacterLocation title="Origen" :location="character.origin" />
                    <CharacterLocation title="Localización actual" :location="character.current_location" />
                </div>
                <CharacterEpisodes :key="character.id" :episodes="character.episodes" />
            </template>
        </div>
    </section>
</template>
