<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import CharacterCard from '../../characters/components/CharacterCard.vue';
import CharacterPagination from '../../characters/components/CharacterPagination.vue';
import { readCharacterQuery, sameCharacterQuery } from '../../characters/services/characterQuery';
import { useFavorites } from '../composables/useFavorites';
import { removeEmptyParams } from '../../../shared/utils/queryParams';
import FavoriteButton from '../components/FavoriteButton.vue';
import FavoriteNotice from '../components/FavoriteNotice.vue';

const route = useRoute();
const router = useRouter();
const { items, loaded, authenticated } = useFavorites();
const heading = ref(null);
const perPage = 20;
const lastPage = computed(() => Math.max(1, Math.ceil(items.value.length / perPage)));
const page = computed(() => Math.min(readCharacterQuery(route.query).page, lastPage.value));
const visible = computed(() => items.value.slice((page.value - 1) * perPage, page.value * perPage));
const meta = computed(() => ({ current_page: page.value, last_page: lastPage.value, per_page: perPage }));
// Los controles emiten números; no se siguen enlaces HTTP del servidor.
const links = computed(() => ({ first: true, last: true, prev: page.value > 1, next: page.value < lastPage.value }));

function pageQuery(number) {
    return removeEmptyParams({ page: number > 1 ? String(number) : undefined });
}

watch([loaded, lastPage, () => route.query], async () => {
    if (!authenticated.value || !loaded.value || route.name !== 'favorites') return;
    const query = pageQuery(page.value);
    if (!sameCharacterQuery(route.query, query)) await router.replace({ name: 'favorites', query });
}, { immediate: true });

// Al retirar una tarjeta, el foco vuelve al título para no perderse en el documento.
watch(() => items.value.length, async (count, previous) => {
    if (loaded.value && count < previous) {
        await nextTick();
        heading.value?.focus();
    }
});
</script>

<template>
    <section aria-labelledby="page-title" class="w-full">
        <h1 id="page-title" ref="heading" tabindex="-1" class="rounded-lg text-3xl font-semibold tracking-tight sm:text-5xl">Tus favoritos</h1>
        <p class="mt-4 text-muted">Los personajes que has guardado con tu cuenta.</p>
        <FavoriteNotice />
        <template v-if="authenticated && loaded">
            <p class="mt-4 text-sm text-muted">{{ items.length }} {{ items.length === 1 ? 'personaje guardado.' : 'personajes guardados.' }}</p>
            <ul v-if="visible.length" aria-label="Personajes favoritos" class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <li v-for="character in visible" :key="character.id" class="min-w-0">
                    <CharacterCard :character="character">
                        <template #actions><FavoriteButton :character="character" /></template>
                    </CharacterCard>
                </li>
            </ul>
            <div v-else class="mt-6 rounded-2xl border border-line bg-white p-8 text-center">
                <h2 class="text-xl font-semibold">Todavía no tienes favoritos</h2>
                <RouterLink :to="{ name: 'characters' }" class="mt-4 inline-flex min-h-11 items-center rounded-lg text-brand-700 underline underline-offset-4">Explorar personajes</RouterLink>
            </div>
            <CharacterPagination v-if="items.length" :meta="meta" :links="links" @page="router.push({ name: 'favorites', query: pageQuery($event) })" />
        </template>
    </section>
</template>
