<script setup>
import { ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import BrandMark from '../components/BrandMark.vue';

const route = useRoute();
const menuOpen = ref(false);
const menuButton = ref(null);
const mainContent = ref(null);
const appName = import.meta.env.VITE_APP_NAME?.trim() || 'Rick and Morty';
const navigation = [
    { name: 'characters', label: 'Personajes' },
    { name: 'favorites', label: 'Favoritos' },
    { name: 'login', label: 'Iniciar sesión' },
    { name: 'register', label: 'Crear cuenta' },
];

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
    document.title = `${route.meta.title} · ${appName}`;
    menuOpen.value = false;

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
            </nav>
        </header>

        <main id="main-content" ref="mainContent" tabindex="-1" class="flex flex-1 flex-col justify-center rounded-lg py-16 sm:py-24">
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
