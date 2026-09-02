<script setup>
import { useFavorites } from '../composables/useFavorites';
const { loading, saving, error, notice, retry } = useFavorites();
</script>

<template>
    <div class="mt-4">
        <p role="status" aria-live="polite" aria-atomic="true" class="text-sm text-muted">{{ saving ? 'Guardando favorito…' : loading ? 'Cargando favoritos…' : notice }}</p>
        <div v-if="error" role="alert" class="mt-3 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-900">
            <p>{{ error.message }}</p>
            <button type="button" :disabled="loading || saving" class="mt-2 min-h-11 rounded-lg font-semibold underline underline-offset-4" @click="retry">{{ error.status === 419 ? 'Renovar CSRF y reintentar' : 'Reintentar favoritos' }}</button>
        </div>
    </div>
</template>
