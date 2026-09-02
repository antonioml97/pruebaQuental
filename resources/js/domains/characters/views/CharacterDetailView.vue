<script setup>
import { RouterLink } from 'vue-router';
import PagePlaceholder from '../../../shared/components/PagePlaceholder.vue';

defineProps({ externalId: { type: String, required: true } });
</script>

<template>
    <PagePlaceholder
        :title="`Personaje #${externalId}`"
        description="Esta será la ficha del personaje, con sus episodios, origen y ubicación. La consulta a la API se incorporará en una próxima entrega; este identificador aún no se ha comprobado."
    >
        <RouterLink :to="{ name: 'characters' }" class="inline-flex min-h-11 items-center rounded-lg font-medium text-brand-700 underline underline-offset-4">
            Volver a personajes
        </RouterLink>
    </PagePlaceholder>
</template>
