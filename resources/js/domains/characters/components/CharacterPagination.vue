<script setup>
import { computed } from 'vue';
const props = defineProps({ meta: { type: Object, required: true }, links: { type: Object, required: true } });
defineEmits(['page']);
const controls = computed(() => [
    { label: 'Primera', page: 1, enabled: props.meta.current_page > 1 && !!props.links.first },
    { label: 'Anterior', page: props.meta.current_page - 1, enabled: props.meta.current_page > 1 && props.meta.current_page <= props.meta.last_page + 1 && !!props.links.prev },
    { label: 'Siguiente', page: props.meta.current_page + 1, enabled: props.meta.current_page < props.meta.last_page && !!props.links.next },
    { label: 'Última', page: props.meta.last_page, enabled: props.meta.current_page < props.meta.last_page && !!props.links.last },
]);
</script>

<template>
    <nav aria-label="Paginación de personajes" class="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-line pt-6">
        <p class="text-sm text-muted">Página {{ meta.current_page }} de {{ meta.last_page }} · {{ meta.per_page }} por página</p>
        <div class="flex flex-wrap gap-2">
            <button v-for="control in controls" :key="control.label" type="button" :disabled="!control.enabled" :aria-label="`${control.label} página`" class="min-h-11 rounded-lg border border-line px-3 py-2 text-sm font-medium enabled:hover:bg-brand-100 disabled:cursor-not-allowed disabled:opacity-40" @click="$emit('page', control.page)">{{ control.label }}</button>
        </div>
    </nav>
</template>
