<script setup>
import { computed } from 'vue';
import { genderOptions, statusOptions } from '../services/characterQuery';
const props = defineProps({ character: { type: Object, required: true } });
const status = computed(() => statusOptions.find((option) => option.value === props.character.status)?.label ?? props.character.status);
const gender = computed(() => genderOptions.find((option) => option.value === props.character.gender)?.label ?? props.character.gender);
</script>

<template>
    <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-3 gap-y-2 text-sm">
        <dt class="text-muted">Estado</dt><dd class="font-medium break-words">{{ status }}</dd>
        <dt class="text-muted">Especie</dt><dd class="break-words">{{ character.species }}</dd>
        <dt class="text-muted">Tipo</dt><dd class="break-words">{{ character.type || 'No especificado' }}</dd>
        <dt class="text-muted">Género</dt><dd class="break-words">{{ gender }}</dd>
    </dl>
</template>
