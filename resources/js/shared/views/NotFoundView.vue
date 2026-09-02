<script setup>
import { RouterLink } from 'vue-router';
import PagePlaceholder from '../components/PagePlaceholder.vue';
</script>

<template>
    <PagePlaceholder title="Este portal no lleva a ninguna parte." eyebrow="Error 404 · Página no encontrada" description="La dirección que has abierto no corresponde a ninguna página de la aplicación.">
        <RouterLink :to="{ name: 'characters' }" class="inline-flex min-h-12 items-center rounded-lg bg-brand-900 px-5 py-3 font-medium text-white hover:bg-brand-700">
            Volver a personajes
        </RouterLink>
    </PagePlaceholder>
</template>
