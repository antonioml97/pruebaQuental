import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createMemoryHistory } from 'vue-router';
import { createAppRouter } from '../../resources/js/router';
import { installSessionGuards, loginDestination } from '../../resources/js/router/sessionGuards';
import { createSession } from '../../resources/js/composables/useSession';
import { ApiError } from '../../resources/js/services/http/ApiError';
import { deferred } from './support/http';

beforeEach(() => vi.stubGlobal('scrollTo', vi.fn()));
afterEach(() => vi.unstubAllGlobals());
const user = { id: 1, name: 'Morty', email: 'morty@example.test' };

async function setup(identity = null) {
    const currentUser = vi.fn(() => identity ? Promise.resolve(identity) : Promise.reject(new ApiError({ status: 401, code: 'unauthenticated', message: 'Invitado' })));
    const session = createSession({ currentUser });
    await session.restore();
    const router = createAppRouter(createMemoryHistory());
    installSessionGuards(router, session);
    return { router, session, currentUser };
}

describe('Guardas de sesión', () => {
    it('lleva al invitado al login conservando ruta, consulta y fragmento', async () => {
        const { router, currentUser } = await setup();
        await router.push('/favorites?page=2#lista');
        expect(router.currentRoute.value.name).toBe('login');
        expect(router.currentRoute.value.query.redirect).toBe('/favorites?page=2#lista');
        expect(currentUser).toHaveBeenCalledTimes(1);
    });

    it.each(['/login', '/register'])('impide que un autenticado abra %s', async (path) => {
        const { router } = await setup(user);
        await router.push(path);
        expect(router.currentRoute.value.name).toBe('characters');
    });

    it('permite favoritos al usuario autenticado', async () => {
        const { router } = await setup(user);
        await router.push('/favorites');
        expect(router.currentRoute.value.name).toBe('favorites');
    });

    it('espera la restauración antes de decidir sin lanzar otra petición', async () => {
        const request = deferred();
        const currentUser = vi.fn(() => request.promise);
        const session = createSession({ currentUser });
        const ready = session.restore();
        const router = createAppRouter(createMemoryHistory());
        installSessionGuards(router, session);
        const navigation = router.push('/favorites');
        expect(router.currentRoute.value.name).toBeUndefined();
        request.resolve(user);
        await ready;
        await navigation;
        expect(router.currentRoute.value.name).toBe('favorites');
        expect(currentUser).toHaveBeenCalledTimes(1);
    });

    it('no bloquea el catálogo público por una restauración pendiente', async () => {
        const request = deferred();
        const session = createSession({ currentUser: () => request.promise });
        const ready = session.restore();
        const router = createAppRouter(createMemoryHistory());
        installSessionGuards(router, session);
        await router.push('/characters');
        expect(session.status.value).toBe('loading');
        expect(router.currentRoute.value.name).toBe('characters');
        request.resolve(user);
        await ready;
    });

    it('un fallo de restauración no abre la ruta privada ni provoca bucles', async () => {
        const session = createSession({ currentUser: () => Promise.reject(new ApiError({ code: 'network_error', message: 'Sin red' })) });
        const ready = session.restore().catch(() => null);
        const router = createAppRouter(createMemoryHistory());
        installSessionGuards(router, session);
        await router.push('/favorites');
        await ready;
        expect(router.currentRoute.value.name).toBe('login');
        expect(session.error.value.code).toBe('network_error');
    });
});

describe('Destino posterior al login', () => {
    it.each(['/favorites?page=2#lista', '/characters/42', '/characters?name=Rick'])('conserva el destino interno %s', (path) => {
        expect(loginDestination(path, createAppRouter(createMemoryHistory()))).toBe(path);
    });

    it.each([undefined, ['//otro.test'], 'https://otro.test', '//otro.test', '/\\otro.test', '/login', '/register', '/docs', '/api/auth/user', '/desconocida', '/%2f%2fotro.test', '/characters\n'])('rechaza destinos externos, inválidos o circulares: %j', (path) => {
        expect(loginDestination(path, createAppRouter(createMemoryHistory()))).toEqual({ name: 'characters' });
    });
});
