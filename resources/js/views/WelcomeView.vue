<script setup>
import BrandMark from '../components/BrandMark.vue';

const appName = import.meta.env.VITE_APP_NAME?.trim() || 'Rick and Morty';
</script>

<template>
    <main class="mx-auto flex min-h-svh max-w-5xl flex-col px-6 py-8 sm:px-10 sm:py-12">
        <header class="flex items-center gap-3">
            <BrandMark />
            <p class="text-sm font-semibold tracking-wide">{{ appName }}</p>
        </header>

        <section aria-labelledby="welcome-title" class="flex flex-1 flex-col justify-center py-20 sm:py-28">
            <p class="mb-6 text-xs font-semibold uppercase tracking-widest text-brand-700">
                Rick and Morty · Aplicación en desarrollo
            </p>
            <h1 id="welcome-title" class="max-w-3xl text-4xl leading-tight font-semibold tracking-tight sm:text-6xl">
                El multiverso empieza aquí.
            </h1>
            <p class="mt-6 max-w-xl text-lg leading-relaxed text-muted">
                La base de la aplicación ya está preparada. El catálogo de personajes,
                las cuentas de usuario y los favoritos llegarán en las próximas entregas.
            </p>
            <a
                href="/docs"
                class="mt-9 inline-flex min-h-12 w-fit items-center gap-3 rounded-lg bg-brand-900 px-5 py-3 font-medium text-white transition-colors hover:bg-brand-700 motion-reduce:transition-none"
            >
                Consultar la documentación de la API
                <span aria-hidden="true">→</span>
            </a>
        </section>

        <footer class="flex flex-col gap-2 border-t border-line pt-6 text-sm text-muted sm:flex-row sm:justify-between">
            <p>Vue 3 · Vite · Tailwind CSS</p>
            <p>Una base para explorar el catálogo.</p>
        </footer>
    </main>
</template>
