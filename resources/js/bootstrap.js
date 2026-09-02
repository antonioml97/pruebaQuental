import { createApp } from 'vue';
import App from './App.vue';
import { createAppRouter } from './router';
import { createApiClient } from './services/http/createApiClient';
import { createAuthenticationService } from './services/authentication/createAuthenticationService';
import { createSession, sessionKey } from './composables/useSession';

/** Monta sin bloquear la pantalla por la red y restaura únicamente la identidad pública. */
export async function mountApplication({ target = '#app', router = createAppRouter(), client = createApiClient() } = {}) {
    const session = createSession(createAuthenticationService(client));
    const app = createApp(App);
    app.provide(sessionKey, session);
    app.use(router);
    await router.isReady();
    app.mount(target);

    // El error permanece en session.error para la futura UI; no hay rechazo sin tratar.
    const ready = session.restore().catch(() => null);
    return { app, session, ready };
}
