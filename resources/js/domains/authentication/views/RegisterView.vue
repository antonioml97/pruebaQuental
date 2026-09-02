<script setup>
import { RouterLink, useRouter } from 'vue-router';
import AuthenticationForm from '../components/AuthenticationForm.vue';
import { useAuthenticationForm } from '../composables/useAuthenticationForm';

const router = useRouter();
const { values, fields, errors, message, busy, csrfExpired, submit } = useAuthenticationForm(
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
