import { createApp } from 'vue';
import App from './App.vue';
import { createAppRouter } from './router';
import { createApiClient } from './shared/services/http/createApiClient';
import { createAuthenticationService } from './domains/authentication';
import { createSession, sessionKey } from './shared/composables/useSession';
import { installSessionGuards } from './router/sessionGuards';
import { characterServiceKey, createCharacterService } from './domains/characters';
import { createFavorites, createFavoriteService, favoritesKey } from './domains/favorites';

/** Monta sin bloquear la pantalla por la red y restaura únicamente la identidad pública. */
export async function mountApplication({ target = '#app', router = createAppRouter(), client = createApiClient() } = {}) {
    const session = createSession(createAuthenticationService(client));
    // Debe empezar antes de la navegación inicial: una guarda privada puede esperarla.
    const ready = session.restore().catch(() => null);
    const removeGuards = installSessionGuards(router, session);
    const app = createApp(App);
    const favorites = createFavorites(createFavoriteService(client), session);
    app.provide(sessionKey, session);
    app.provide(favoritesKey, favorites);
    app.provide(characterServiceKey, createCharacterService(client));
    app.onUnmount(() => favorites.dispose());
    app.onUnmount(removeGuards);
    app.use(router);
    app.mount(target);
    await router.isReady();

    // Los errores de restauración permanecen en session.error para la interfaz.
    return { app, session, ready };
}
