<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useFavorites } from '../composables/useFavorites';

const props = defineProps({ character: { type: Object, required: true } });
const route = useRoute();
const favorites = useFavorites();
const { authenticated, disabled, loaded, sessionLoading } = favorites;
const selected = computed(() => favorites.has(props.character.id));
const label = computed(() => selected.value ? 'Quitar de favoritos' : 'Añadir a favoritos');

function toggleFavorite() {
    if (selected.value) return favorites.remove(props.character);
    return favorites.add(props.character);
}
</script>

<template>
    <button v-if="authenticated || sessionLoading" type="button" :disabled="disabled" :aria-pressed="selected" :aria-label="`${label}: ${character.name}`" class="min-h-11 rounded-lg border border-line px-4 py-2 text-sm font-semibold enabled:hover:bg-brand-100 disabled:cursor-wait disabled:opacity-60" :class="{ 'bg-brand-100 text-brand-900': selected }" @click="toggleFavorite">
        {{ sessionLoading ? 'Comprobando sesión…' : loaded ? label : 'Comprobando favoritos…' }}
    </button>
    <RouterLink v-else :to="{ name: 'login', query: { redirect: route.fullPath } }" class="inline-flex min-h-11 items-center rounded-lg text-sm text-brand-700 underline underline-offset-4">Inicia sesión para guardar favoritos</RouterLink>
</template>
