<script setup>
import { reactive, watch } from 'vue';
import { genderOptions, statusOptions } from '../services/characterQuery';

const props = defineProps({ criteria: { type: Object, required: true } });
const emit = defineEmits(['apply']);
const draft = reactive({ name: '', status: '', species: '', gender: '' });
watch(() => props.criteria, (value) => {
    for (const field of Object.keys(draft)) draft[field] = value[field];
}, { immediate: true });

function clear() {
    for (const field of Object.keys(draft)) draft[field] = '';
    emit('apply', { ...draft });
}
</script>

<template>
    <form role="search" aria-label="Filtrar personajes" class="mt-8 rounded-2xl border border-line bg-white p-5 sm:p-6" @submit.prevent="emit('apply', { ...draft })">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="character-name" class="mb-2 block text-sm font-semibold">Nombre</label>
                <input id="character-name" v-model="draft.name" name="name" type="search" maxlength="255" placeholder="Ej.: Rick" class="min-h-12 w-full rounded-lg border border-line bg-canvas px-3 py-2">
            </div>
            <div>
                <label for="character-status" class="mb-2 block text-sm font-semibold">Estado</label>
                <select id="character-status" v-model="draft.status" name="status" class="min-h-12 w-full rounded-lg border border-line bg-canvas px-3 py-2">
                    <option value="">Todos los estados</option>
                    <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
            </div>
            <div>
                <label for="character-species" class="mb-2 block text-sm font-semibold">Especie</label>
                <input id="character-species" v-model="draft.species" name="species" type="search" maxlength="255" placeholder="Ej.: Human" class="min-h-12 w-full rounded-lg border border-line bg-canvas px-3 py-2">
            </div>
            <div>
                <label for="character-gender" class="mb-2 block text-sm font-semibold">Género</label>
                <select id="character-gender" v-model="draft.gender" name="gender" class="min-h-12 w-full rounded-lg border border-line bg-canvas px-3 py-2">
                    <option value="">Todos los géneros</option>
                    <option v-for="option in genderOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
            </div>
        </div>
        <div class="mt-5 flex flex-wrap items-center gap-3">
            <button type="submit" class="min-h-12 rounded-lg bg-brand-900 px-5 py-2 font-semibold text-white hover:bg-brand-700">Buscar personajes</button>
            <button type="button" class="min-h-12 rounded-lg border border-line px-5 py-2 font-medium hover:bg-brand-100" @click="clear">Limpiar filtros</button>
            <p class="text-sm text-muted">Aplica los filtros para actualizar la búsqueda.</p>
        </div>
    </form>
</template>
