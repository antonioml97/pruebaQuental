import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createMemoryHistory } from 'vue-router';
import { mountApplication } from '../../resources/js/bootstrap';
import { createAppRouter } from '../../resources/js/router';
import { createApiClient } from '../../resources/js/shared/services/http/createApiClient';
import { deferred, rejectResponse, response } from './support/http';

let mounted;
beforeEach(() => {
    vi.stubGlobal('scrollTo', vi.fn());
    document.body.innerHTML = '<div id="app"></div>';
});
afterEach(() => {
    mounted?.app.unmount();
    mounted = undefined;
    vi.unstubAllGlobals();
    document.body.replaceChildren();
});

async function start(adapter) {
    const router = createAppRouter(createMemoryHistory());
    await router.push('/characters');
    mounted = await mountApplication({ router, client: createApiClient({ adapter }) });
    return mounted;
}

describe('Arranque y recuperación de sesión', () => {
    it('monta la pantalla mientras recupera al usuario, sin leer ni escribir almacenamiento persistente', async () => {
        const storageGet = vi.spyOn(Storage.prototype, 'getItem');
        const storageSet = vi.spyOn(Storage.prototype, 'setItem');
        const request = deferred();
        const adapter = vi.fn((config) => request.promise.then((data) => response(config, data)));
        const { session, ready } = await start(adapter);
        expect(document.querySelector('h1').textContent).toBe('Explora el multiverso.');
        expect(session.status.value).toBe('loading');
        request.resolve({ data: { id: 1, name: 'Morty', email: 'morty@example.test' } });
        await ready;
        expect(session.status.value).toBe('authenticated');
        expect(adapter.mock.calls[0][0].url).toBe('/auth/user');
        expect(adapter).toHaveBeenCalledTimes(1);
        expect(storageGet).not.toHaveBeenCalled();
        expect(storageSet).not.toHaveBeenCalled();
    });

    it.each([401, 419, 500])('no impide montar la aplicación ante %i ni repite la petición', async (status) => {
        const adapter = vi.fn((config) => rejectResponse(config, status, 'error_de_prueba'));
        const { session, ready } = await start(adapter);
        await ready;
        expect(session.status.value).toBe('guest');
        expect(document.querySelector('h1')).not.toBeNull();
        expect(adapter).toHaveBeenCalledTimes(1);
        expect(session.error.value?.status ?? null).toBe(status === 401 ? null : status);
    });

    it.each([true, false])('resuelve una entrada privada durante la restauración, autenticado=%s', async (authenticated) => {
        const request = deferred();
        const history = createMemoryHistory();
        history.push('/favorites?page=2');
        const router = createAppRouter(history);
        const adapter = vi.fn((config) => request.promise.then(() => authenticated
            ? response(config, { data: { id: 1, name: 'Morty', email: 'morty@example.test' } })
            : rejectResponse(config, 401, 'unauthenticated')));
        const start = mountApplication({ router, client: createApiClient({ adapter }) });
        expect(document.body.textContent).toContain('Preparando la página');
        request.resolve();
        mounted = await start;
        await mounted.ready;
        expect(router.currentRoute.value.name).toBe(authenticated ? 'favorites' : 'login');
        expect(document.querySelector('h1').textContent).toBe(authenticated ? 'Tus favoritos' : 'Iniciar sesión');
        expect(adapter).toHaveBeenCalledTimes(1);
    });
});
