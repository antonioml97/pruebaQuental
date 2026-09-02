<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import { readCharacterQuery, writeCharacterQuery } from '../services/characterQuery';
import CharacterImage from './CharacterImage.vue';
import CharacterFacts from './CharacterFacts.vue';

const props = defineProps({ character: { type: Object, required: true }, catalogQuery: { type: Object, default: () => ({}) } });
const detailQuery = computed(() => writeCharacterQuery(readCharacterQuery(props.catalogQuery)));
</script>

<template>
    <article class="h-full overflow-hidden rounded-2xl border border-line bg-white">
        <CharacterImage :character="character" />
        <div class="p-5">
            <p class="mb-2 text-xs font-semibold tracking-wider text-muted">PERSONAJE #{{ character.id }}</p>
            <h2 class="text-xl font-semibold break-words">
                <RouterLink :to="{ name: 'character-detail', params: { externalId: character.id }, query: detailQuery }" class="rounded-sm underline decoration-line underline-offset-4 hover:text-brand-700">{{ character.name }}</RouterLink>
            </h2>
            <CharacterFacts :character="character" class="mt-4" />
            <div v-if="$slots.actions" class="mt-5"><slot name="actions" /></div>
        </div>
    </article>
</template>
