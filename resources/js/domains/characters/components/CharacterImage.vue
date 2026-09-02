<script setup>
import { computed, ref, watch } from 'vue';
const props = defineProps({ character: { type: Object, required: true }, eager: { type: Boolean, default: false } });
const failed = ref(false);
const source = computed(() => /^https?:\/\//i.test(props.character.image_url) ? props.character.image_url : '');
watch(() => props.character.image_url, () => { failed.value = false; });
</script>

<template>
    <img v-if="source && !failed" :src="source" :alt="`Retrato de ${character.name}`" width="300" height="300" :loading="eager ? 'eager' : 'lazy'" decoding="async" referrerpolicy="no-referrer" class="aspect-square w-full bg-brand-100 object-cover" @error="failed = true">
    <div v-else class="flex aspect-square items-center justify-center bg-brand-100 p-6 text-center text-muted" role="img" :aria-label="`Imagen no disponible de ${character.name}`">Imagen no disponible</div>
</template>
