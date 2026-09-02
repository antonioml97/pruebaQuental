<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import BrandMark from '../components/BrandMark.vue';
import { useSession } from '../composables/useSession';

const route = useRoute();
const router = useRouter();
const session = useSession();
const { user, status } = session;
const menuOpen = ref(false);
const menuButton = ref(null);
const mainContent = ref(null);
const appName = import.meta.env.VITE_APP_NAME?.trim() || 'Rick and Morty';
const logoutPending = ref(false);
const logoutMessage = ref('');
const logoutCsrfExpired = ref(false);
const logoutAlert = ref(null);
const notice = ref('');
const navigation = computed(() => [
    { name: 'characters', label: 'Personajes' },
    { name: 'favorites', label: 'Favoritos' },
    ...(status.value === 'guest' ? [
        { name: 'login', label: 'Iniciar sesión' },
        { name: 'register', label: 'Crear cuenta' },
    ] : []),
]);

async function logout() {
    if (logoutPending.value || status.value === 'loading') return;
    logoutPending.value = true;
    logoutMessage.value = '';
    logoutCsrfExpired.value = false;
    let succeeded = false;
    try {
        await session.logout();
        succeeded = true;
    } catch (error) {
        logoutCsrfExpired.value = error.status === 419;
        logoutMessage.value = logoutCsrfExpired.value
            ? 'No se ha cerrado la sesión: la protección CSRF no es válida. Puedes renovarla y volver a intentarlo.'
            : error.message;
    } finally {
        logoutPending.value = false;
    }
    if (succeeded) {
        menuOpen.value = false;
        await router.replace({ name: 'characters' });
        notice.value = 'Has cerrado la sesión.';
        await nextTick();
        focusContent();
    } else {
        await nextTick();
        logoutAlert.value?.focus();
    }
}

function focusContent() {
    mainContent.value?.focus({ preventScroll: true });
}

function closeMenu() {
    if (menuOpen.value) {
        menuOpen.value = false;
        focusContent();
    }
}

function dismissMenu() {
    if (menuOpen.value) {
        menuOpen.value = false;
        menuButton.value?.focus();
    }
}

watch(() => route.fullPath, (path, previousPath) => {
    if (route.meta.title) document.title = `${route.meta.title} · ${appName}`;
    menuOpen.value = false;
    logoutMessage.value = '';

    // Tras una navegación, el teclado continúa en el contenido recién abierto.
    // En la carga inicial se conserva el orden natural, incluido el enlace de salto.
    if (previousPath !== undefined) {
        focusContent();
    }
}, { immediate: true, flush: 'post' });
</script>

<template>
    <a
        href="#main-content"
        class="sr-only z-10 rounded-lg bg-brand-900 px-5 py-3 text-white focus:not-sr-only focus:fixed focus:top-4 focus:left-4"
        @click.prevent="focusContent"
    >
        Saltar al contenido
    </a>
    <div class="mx-auto flex min-h-svh max-w-6xl flex-col px-6 py-6 sm:px-10 sm:py-8">
        <header class="flex flex-wrap items-center justify-between gap-4 border-b border-line pb-6" @keydown.esc="dismissMenu">
            <RouterLink :to="{ name: 'characters' }" class="flex min-w-0 items-center gap-3 rounded-lg">
                <BrandMark />
                <span class="break-words text-sm font-semibold tracking-wide">{{ appName }}</span>
            </RouterLink>
            <button
                ref="menuButton"
                type="button"
                class="min-h-11 rounded-lg border border-line px-4 py-2 text-sm font-semibold hover:bg-brand-100 md:hidden"
                aria-controls="main-navigation"
                :aria-expanded="menuOpen"
                @click="menuOpen = !menuOpen"
            >
                {{ menuOpen ? 'Cerrar menú' : 'Abrir menú' }}
            </button>
            <nav
                id="main-navigation"
                aria-label="Navegación principal"
                class="w-full flex-col gap-2 md:flex md:w-auto md:flex-row"
                :class="menuOpen ? 'flex' : 'hidden'"
            >
                <RouterLink
                    v-for="item in navigation"
                    :key="item.name"
                    :to="{ name: item.name }"
                    class="inline-flex min-h-11 items-center rounded-lg px-4 py-2 text-sm font-medium hover:bg-brand-100"
                    exact-active-class="bg-brand-100 text-brand-900"
                    @click="closeMenu"
                >
                    {{ item.label }}
                </RouterLink>
                <template v-if="user">
                    <span class="inline-flex max-w-44 items-center px-2 text-sm break-words text-muted">Hola, {{ user.name }}</span>
                    <button type="button" :disabled="logoutPending || status === 'loading'" class="min-h-11 rounded-lg border border-line px-4 py-2 text-sm font-medium hover:bg-brand-100 disabled:cursor-wait disabled:opacity-60" @click="logout">
                        {{ logoutPending ? 'Cerrando sesión…' : 'Cerrar sesión' }}
                    </button>
                </template>
                <span v-else-if="status === 'loading'" role="status" class="inline-flex min-h-11 items-center px-4 text-sm text-muted">Comprobando sesión…</span>
            </nav>
        </header>

        <main id="main-content" ref="mainContent" tabindex="-1" class="flex flex-1 flex-col justify-center rounded-lg py-16 sm:py-24">
            <div v-if="logoutMessage" ref="logoutAlert" role="alert" tabindex="-1" class="mb-6 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-900">
                <p>{{ logoutMessage }}</p>
                <button v-if="logoutCsrfExpired" type="button" :disabled="logoutPending" class="mt-2 min-h-11 rounded-lg font-semibold underline underline-offset-4" @click="logout">Renovar CSRF y cerrar sesión</button>
            </div>
            <p role="status" class="sr-only">{{ notice }}</p>
            <slot />
        </main>

        <footer class="flex flex-col gap-3 border-t border-line pt-6 text-sm text-muted sm:flex-row sm:items-center sm:justify-between">
            <p>Vue 3 · Vite · Tailwind CSS</p>
            <a href="/docs" class="inline-flex min-h-11 items-center rounded-lg underline underline-offset-4 hover:text-brand-700">
                Documentación de la API <span aria-hidden="true" class="ml-2">↗</span>
            </a>
        </footer>
    </div>
</template>
