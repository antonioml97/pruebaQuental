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
    // Servicios HTTP: se encargan de las peticiones al backend.
    const authenticationService = createAuthenticationService(client);
    const characterService = createCharacterService(client);
    const favoriteService = createFavoriteService(client);

    const session = createSession(authenticationService);
    // Debe empezar antes de la navegación inicial: una guarda privada puede esperarla.
    const ready = session.restore().catch(() => null);
    const removeGuards = installSessionGuards(router, session);
    const app = createApp(App);
    // Estado de favoritos compartido entre las pantallas y ligado a la sesión.
    const favorites = createFavorites(favoriteService, session);
    app.provide(sessionKey, session);
    app.provide(favoritesKey, favorites);
    app.provide(characterServiceKey, characterService);
    app.onUnmount(() => favorites.dispose());
    app.onUnmount(removeGuards);
    app.use(router);
    app.mount(target);
    await router.isReady();

    // Los errores de restauración permanecen en session.error para la interfaz.
    return { app, session, ready };
}
