import { createRouter, createWebHistory } from 'vue-router';

// Una instancia por aplicación o prueba evita compartir el historial y sus guardas.
export function createAppRouter(history = createWebHistory()) {
    return createRouter({
        history,
        routes: [
            { path: '/', redirect: { name: 'characters' } },
            {
                path: '/characters',
                name: 'characters',
                component: () => import('../domains/characters/views/CharactersView.vue'),
                meta: { title: 'Personajes', access: 'public' },
            },
            {
                path: '/characters/:externalId([1-9]\\d*)',
                name: 'character-detail',
                component: () => import('../domains/characters/views/CharacterDetailView.vue'),
                props: true,
                meta: { title: 'Detalle del personaje', access: 'public' },
            },
            {
                path: '/login',
                name: 'login',
                component: () => import('../domains/authentication/views/LoginView.vue'),
                meta: { title: 'Iniciar sesión', access: 'guest' },
            },
            {
                path: '/register',
                name: 'register',
                component: () => import('../domains/authentication/views/RegisterView.vue'),
                meta: { title: 'Crear cuenta', access: 'guest' },
            },
            {
                path: '/favorites',
                name: 'favorites',
                component: () => import('../domains/favorites/views/FavoritesView.vue'),
                meta: { title: 'Favoritos', access: 'authenticated' },
            },
            {
                path: '/:pathMatch(.*)*',
                name: 'not-found',
                component: () => import('../shared/views/NotFoundView.vue'),
                meta: { title: 'Página no encontrada', access: 'public' },
            },
        ],
        scrollBehavior(to, from, savedPosition) {
            return savedPosition || { top: 0 };
        },
    });
}
