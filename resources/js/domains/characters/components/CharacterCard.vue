<script setup>
import { computed, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { genderOptions, statusOptions } from '../services/characterQuery';

const props = defineProps({ character: { type: Object, required: true } });
const failedImage = ref(false);
const imageUrl = computed(() => /^https?:\/\//i.test(props.character.image_url) ? props.character.image_url : '');
watch(() => props.character.image_url, () => { failedImage.value = false; });
const status = computed(() => statusOptions.find((option) => option.value === props.character.status)?.label ?? props.character.status);
const gender = computed(() => genderOptions.find((option) => option.value === props.character.gender)?.label ?? props.character.gender);
</script>

<template>
    <article class="h-full overflow-hidden rounded-2xl border border-line bg-white">
        <img v-if="imageUrl && !failedImage" :src="imageUrl" :alt="`Retrato de ${character.name}`" width="300" height="300" loading="lazy" decoding="async" referrerpolicy="no-referrer" class="aspect-square w-full bg-brand-100 object-cover" @error="failedImage = true">
        <div v-else class="flex aspect-square items-center justify-center bg-brand-100 p-6 text-center text-muted" role="img" :aria-label="`Imagen no disponible de ${character.name}`">Imagen no disponible</div>
        <div class="p-5">
            <p class="mb-2 text-xs font-semibold tracking-wider text-muted">PERSONAJE #{{ character.id }}</p>
            <h2 class="text-xl font-semibold break-words">
                <RouterLink :to="{ name: 'character-detail', params: { externalId: character.id } }" class="rounded-sm underline decoration-line underline-offset-4 hover:text-brand-700">{{ character.name }}</RouterLink>
            </h2>
            <dl class="mt-4 grid grid-cols-[auto_minmax(0,1fr)] gap-x-3 gap-y-2 text-sm">
                <dt class="text-muted">Estado</dt><dd class="font-medium break-words">{{ status }}</dd>
                <dt class="text-muted">Especie</dt><dd class="break-words">{{ character.species }}</dd>
                <dt class="text-muted">Tipo</dt><dd class="break-words">{{ character.type || 'No especificado' }}</dd>
                <dt class="text-muted">Género</dt><dd class="break-words">{{ gender }}</dd>
            </dl>
        </div>
    </article>
</template>
