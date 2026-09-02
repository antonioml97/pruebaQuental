<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, required: true },
    eyebrow: { type: String, default: 'Rick and Morty · Aplicación en desarrollo' },
});
</script>

<template>
    <section aria-labelledby="page-title">
        <p class="mb-6 text-xs font-semibold uppercase tracking-widest text-brand-700">{{ eyebrow }}</p>
        <h1 id="page-title" class="max-w-3xl text-4xl leading-tight font-semibold tracking-tight break-words sm:text-6xl">{{ title }}</h1>
        <p class="mt-6 max-w-xl text-lg leading-relaxed text-muted">{{ description }}</p>
        <div v-if="$slots.default" class="mt-8"><slot /></div>
    </section>
</template>
