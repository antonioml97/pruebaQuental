<script setup>
import CharacterImage from './CharacterImage.vue';
import CharacterFacts from './CharacterFacts.vue';
defineProps({ character: { type: Object, required: true } });
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-line bg-white sm:grid sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
        <CharacterImage :character="character" eager class="h-full" />
        <div class="p-6 sm:p-8">
            <h2 class="text-xl font-semibold">Datos del personaje</h2>
            <p class="mt-2 text-sm text-muted">Identificador #{{ character.id }}</p>
            <CharacterFacts :character="character" class="mt-6" />
            <!-- Punto de extensión para #31: no consulta ni modifica favoritos por sí mismo. -->
            <div v-if="$slots.actions" class="mt-6"><slot name="actions" :character="character" /></div>
        </div>
    </div>
</template>
