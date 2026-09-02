import { createApp } from 'vue';
import App from './App.vue';
import { createAppRouter } from './router';
import { createApiClient } from './shared/services/http/createApiClient';
import { createAuthenticationService } from './domains/authentication';
import { createSession, sessionKey } from './shared/composables/useSession';
import { installSessionGuards } from './router/sessionGuards';

/** Monta sin bloquear la pantalla por la red y restaura únicamente la identidad pública. */
export async function mountApplication({ target = '#app', router = createAppRouter(), client = createApiClient() } = {}) {
    const session = createSession(createAuthenticationService(client));
    // Debe empezar antes de la navegación inicial: una guarda privada puede esperarla.
    const ready = session.restore().catch(() => null);
    const removeGuards = installSessionGuards(router, session);
    const app = createApp(App);
    app.provide(sessionKey, session);
    app.onUnmount(removeGuards);
    app.use(router);
    app.mount(target);
    await router.isReady();

    // Los errores de restauración permanecen en session.error para la interfaz.
    return { app, session, ready };
}
