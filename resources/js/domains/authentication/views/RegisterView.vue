<script setup>
import { RouterLink, useRouter } from 'vue-router';
import AuthenticationForm from '../components/AuthenticationForm.vue';
import { useAuthenticationForm } from '../composables/useAuthenticationForm';

const router = useRouter();
const fields = [
    { name: 'name', label: 'Nombre', type: 'text', autocomplete: 'name', maxlength: 255 },
    { name: 'email', label: 'Correo electrónico', type: 'email', autocomplete: 'email', maxlength: 255 },
    { name: 'password', label: 'Contraseña', type: 'password', autocomplete: 'new-password', maxlength: 72,
        hint: 'Entre 8 y 72 caracteres, con mayúsculas, minúsculas y números.' },
    { name: 'password_confirmation', label: 'Repetir contraseña', type: 'password', autocomplete: 'new-password', maxlength: 72 },
];
const { values, errors, message, busy, csrfExpired, submit } = useAuthenticationForm(
    'register', () => router.replace({ name: 'characters' }),
);
</script>

<template>
    <AuthenticationForm v-model="values" :fields="fields" :errors="errors" :message="message" :busy="busy" :csrf-expired="csrfExpired"
        title="Crear cuenta" description="Regístrate y empieza tu colección de personajes del multiverso." submit-label="Crear cuenta" @submit="submit">
        ¿Ya tienes cuenta?
        <RouterLink :to="{ name: 'login' }" class="inline-flex min-h-11 items-center rounded-lg font-semibold text-brand-700 underline underline-offset-4">Iniciar sesión</RouterLink>
    </AuthenticationForm>
</template>
