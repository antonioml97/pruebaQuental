<script setup>
import { RouterLink, useRoute, useRouter } from 'vue-router';
import AuthenticationForm from '../components/AuthenticationForm.vue';
import { useAuthenticationForm } from '../composables/useAuthenticationForm';
import { loginDestination } from '../router/sessionGuards';

const route = useRoute();
const router = useRouter();
const { values, fields, errors, message, busy, csrfExpired, submit } = useAuthenticationForm(
    'login', () => router.replace(loginDestination(route.query.redirect, router)),
);
</script>

<template>
    <AuthenticationForm v-model="values" :fields="fields" :errors="errors" :message="message" :busy="busy" :csrf-expired="csrfExpired"
        title="Iniciar sesión" description="Accede a tu cuenta para guardar y consultar tus personajes favoritos." submit-label="Entrar" @submit="submit">
        ¿Todavía no tienes cuenta?
        <RouterLink :to="{ name: 'register' }" class="inline-flex min-h-11 items-center rounded-lg font-semibold text-brand-700 underline underline-offset-4">Crear cuenta</RouterLink>
    </AuthenticationForm>
</template>
