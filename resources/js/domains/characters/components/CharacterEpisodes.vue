<script setup>
import { computed, nextTick, ref, watch } from 'vue';
const props = defineProps({ episodes: { type: Array, required: true } });
const visibleCount = ref(20);
const list = ref(null);
const visible = computed(() => props.episodes.slice(0, visibleCount.value));
watch(() => props.episodes, () => { visibleCount.value = 20; });
const dateFormatter = new Intl.DateTimeFormat('es-ES', { dateStyle: 'long', timeZone: 'UTC' });
function dateLabel(value) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return null;
    const date = new Date(`${value}T00:00:00Z`);
    return !Number.isNaN(date.valueOf()) && date.toISOString().startsWith(value) ? dateFormatter.format(date) : null;
}
async function showMore() {
    const firstNew = visible.value.length;
    visibleCount.value += 20;
    await nextTick();
    list.value?.children[firstNew]?.focus();
}
</script>

<template>
    <section aria-labelledby="episodes-title" class="mt-8 rounded-2xl border border-line bg-white p-6 sm:p-8">
        <h2 id="episodes-title" class="text-xl font-semibold">Episodios</h2>
        <p v-if="!episodes.length" class="mt-4 text-muted">No hay episodios disponibles para este personaje.</p>
        <template v-else>
            <p role="status" aria-live="polite" class="mt-2 text-sm text-muted">Mostrando {{ visible.length }} de {{ episodes.length }} episodios.</p>
            <ol ref="list" id="character-episodes" class="mt-6 divide-y divide-line">
                <li v-for="episode in visible" :key="episode.id" tabindex="-1" class="flex flex-col gap-2 rounded-sm py-4 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                    <div class="min-w-0"><p class="text-xs font-semibold tracking-wider text-brand-700">{{ episode.code }}</p><h3 class="mt-1 font-medium break-words">{{ episode.name }}</h3></div>
                    <time v-if="dateLabel(episode.air_date)" :datetime="episode.air_date" class="shrink-0 text-sm text-muted">{{ dateLabel(episode.air_date) }}</time>
                    <span v-else class="text-sm text-muted">Fecha no disponible</span>
                </li>
            </ol>
            <button v-if="visible.length < episodes.length" type="button" aria-controls="character-episodes" class="mt-6 min-h-12 rounded-lg border border-line px-5 py-2 font-semibold hover:bg-brand-100" @click="showMore">Mostrar más episodios</button>
        </template>
    </section>
</template>
