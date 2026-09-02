<script setup>
import { computed, nextTick, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: Object, required: true },
    fields: { type: Array, required: true },
    title: { type: String, required: true },
    description: { type: String, required: true },
    submitLabel: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
    message: { type: String, default: '' },
    busy: { type: Boolean, default: false },
    csrfExpired: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue', 'submit']);
const summary = ref(null);
const errorList = computed(() => Object.entries(props.errors).flatMap(([field, messages]) =>
    messages.map((message) => ({ field, message, known: props.fields.some((item) => item.name === field) }))));

watch([() => props.errors, () => props.message], async () => {
    if (props.message) {
        await nextTick();
        summary.value?.focus();
    }
});

function updateField(name, value) {
    emit('update:modelValue', { ...props.modelValue, [name]: value });
}
</script>

<template>
    <section aria-labelledby="page-title" class="mx-auto w-full max-w-lg">
        <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-brand-700">Tu espacio en el multiverso</p>
        <h1 id="page-title" class="text-3xl font-semibold tracking-tight sm:text-4xl">{{ title }}</h1>
        <p class="mt-4 text-base leading-relaxed text-muted">{{ description }}</p>

        <form class="mt-8 rounded-2xl border border-line bg-white p-5 sm:p-8" novalidate :aria-busy="busy" @submit.prevent="emit('submit')">
            <div v-if="message" ref="summary" role="alert" tabindex="-1" class="mb-6 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-900">
                <p class="font-semibold">{{ message }}</p>
                <ul v-if="errorList.length" class="mt-2 list-disc space-y-1 pl-5">
                    <li v-for="(error, index) in errorList" :key="index">
                        <a v-if="error.known" :href="`#auth-${error.field}`" class="underline underline-offset-2">{{ error.message }}</a>
                        <span v-else>{{ error.message }}</span>
                    </li>
                </ul>
            </div>

            <fieldset :disabled="busy" class="space-y-5">
                <legend class="sr-only">Datos para {{ title.toLowerCase() }}</legend>
                <div v-for="field in fields" :key="field.name">
                    <label :for="`auth-${field.name}`" class="mb-2 block text-sm font-semibold">{{ field.label }}</label>
                    <input
                        :id="`auth-${field.name}`"
                        :name="field.name"
                        :type="field.type"
                        :autocomplete="field.autocomplete"
                        :maxlength="field.maxlength"
                        :value="modelValue[field.name]"
                        required
                        :aria-invalid="errors[field.name]?.length ? 'true' : undefined"
                        :aria-describedby="[field.hint ? `hint-${field.name}` : '', errors[field.name]?.length ? `error-${field.name}` : ''].filter(Boolean).join(' ') || undefined"
                        class="min-h-12 w-full rounded-lg border border-line bg-canvas px-3 py-2 text-base disabled:opacity-60"
                        @input="updateField(field.name, $event.target.value)"
                    >
                    <p v-if="field.hint" :id="`hint-${field.name}`" class="mt-2 text-sm text-muted">{{ field.hint }}</p>
                    <ul v-if="errors[field.name]?.length" :id="`error-${field.name}`" class="mt-2 space-y-1 text-sm text-red-800">
                        <li v-for="error in errors[field.name]" :key="error">{{ error }}</li>
                    </ul>
                </div>
                <button type="submit" class="min-h-12 w-full rounded-lg bg-brand-900 px-5 py-3 font-semibold text-white hover:bg-brand-700 disabled:cursor-wait disabled:opacity-60">
                    {{ busy ? 'Procesando…' : csrfExpired ? 'Renovar CSRF y reintentar' : submitLabel }}
                </button>
            </fieldset>
        </form>
        <p v-if="busy" role="status" class="mt-3 text-sm text-muted">Espera mientras se completa la operación.</p>

        <div class="mt-6 text-center text-sm text-muted"><slot /></div>
    </section>
</template>
