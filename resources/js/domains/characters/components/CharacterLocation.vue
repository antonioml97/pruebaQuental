<script setup>
defineProps({ title: { type: String, required: true }, location: { type: Object, default: null } });
function known(value, fallback) {
    return value?.trim() && value.trim().toLowerCase() !== 'unknown' ? value : fallback;
}
</script>

<template>
    <section class="min-w-0 rounded-2xl border border-line bg-white p-6">
        <h2 class="text-xl font-semibold">{{ title }}</h2>
        <p class="mt-4 font-medium break-words">{{ known(location?.name, 'Desconocido') }}</p>
        <dl v-if="location" class="mt-4 grid grid-cols-[auto_minmax(0,1fr)] gap-x-3 gap-y-2 text-sm">
            <dt class="text-muted">Tipo</dt><dd class="break-words">{{ known(location.type, 'No especificado') }}</dd>
            <dt class="text-muted">Dimensión</dt><dd class="break-words">{{ known(location.dimension, 'Desconocida') }}</dd>
        </dl>
    </section>
</template>
