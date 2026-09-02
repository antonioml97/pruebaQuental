<script setup>
import { RouterView, useRoute } from 'vue-router';
import MainLayout from './shared/layouts/MainLayout.vue';
const route = useRoute();
</script>

<template>
    <MainLayout>
        <RouterView v-if="route.name" />
        <p v-else role="status" class="text-muted">Preparando la página…</p>
    </MainLayout>
</template>
