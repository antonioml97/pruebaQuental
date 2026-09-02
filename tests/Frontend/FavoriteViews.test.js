import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises } from '@vue/test-utils';
import { createMemoryHistory } from 'vue-router';
import { mountApplication } from '../../resources/js/bootstrap';
import { createAppRouter } from '../../resources/js/router';
import { createApiClient } from '../../resources/js/shared/services/http/createApiClient';
import { character, characterDetail, characterPage } from './support/characters';
import { deferred, rejectResponse, response } from './support/http';

let mounted;
beforeEach(() => {
    vi.stubGlobal('scrollTo', vi.fn());
    document.body.innerHTML = '<div id="app"></div>';
});
afterEach(() => { mounted?.app.unmount(); mounted = null; vi.unstubAllGlobals(); document.body.replaceChildren(); });

async function start(path = '/characters', { data = [], guest = false, mutation, listFailure } = {}) {
    let saved = [...data];
    const adapter = vi.fn((config) => {
        if (config.url === '/auth/user') return guest ? rejectResponse(config, 401, 'unauthenticated')
            : Promise.resolve(response(config, { data: { id: 1, name: 'Morty', email: 'morty@example.test' } }));
        if (config.url === '/auth/csrf-cookie') return Promise.resolve(response(config, '', 204));
        if (config.url === '/characters') return Promise.resolve(response(config, characterPage()));
        if (config.url === '/characters/1') return Promise.resolve(response(config, { data: characterDetail() }));
        if (config.url === '/favorites') return listFailure ? listFailure(config)
            : Promise.resolve(response(config, characterPage({ data: saved.slice((config.params.page - 1) * 100, config.params.page * 100), page: config.params.page, perPage: 100, total: saved.length })));
        if (mutation) return mutation(config);
        if (config.method === 'put') {
            saved = [character, ...saved.filter((item) => item.id !== character.id)];
            return Promise.resolve(response(config, { data: character }));
        }
        if (config.method === 'delete') {
            saved = saved.filter((item) => item.id !== Number(config.url.split('/').pop()));
            return Promise.resolve(response(config, '', 204));
        }
        throw new Error(`Petición no prevista: ${config.url}`);
    });
    const router = createAppRouter(createMemoryHistory());
    await router.push(path);
    mounted = await mountApplication({ router, client: createApiClient({ adapter }) });
    await mounted.ready;
    await flushPromises();
    return { ...mounted, router, adapter };
}

function favoriteButton() { return document.querySelector('button[aria-pressed]'); }
function button(text) { return [...document.querySelectorAll('button')].find((item) => item.textContent.includes(text)); }

describe('Favoritos en catálogo, detalle y vista privada', () => {
    it('comparte alta y baja confirmadas entre las tres vistas sin recargar la colección', async () => {
        const { router, adapter } = await start();
        expect(favoriteButton().getAttribute('aria-pressed')).toBe('false');
        expect(favoriteButton().getAttribute('aria-label')).toBe('Añadir a favoritos: Rick Sanchez');
        favoriteButton().click();
        await flushPromises();
        expect(favoriteButton().getAttribute('aria-pressed')).toBe('true');
        expect(document.body.textContent).toContain('Rick Sanchez: añadido a favoritos.');
        await router.push('/characters/1');
        await flushPromises();
        expect(favoriteButton().getAttribute('aria-pressed')).toBe('true');
        await router.push('/favorites');
        await flushPromises();
        expect(document.querySelectorAll('article')).toHaveLength(1);
        favoriteButton().click();
        await flushPromises();
        expect(document.body.textContent).toContain('Todavía no tienes favoritos');
        await router.push('/characters');
        await flushPromises();
        expect(favoriteButton().getAttribute('aria-pressed')).toBe('false');
        expect(adapter.mock.calls.filter(([config]) => config.url === '/favorites')).toHaveLength(1);
        expect(adapter.mock.calls.filter(([config]) => config.url === '/auth/csrf-cookie')).toHaveLength(2);
    });

    it('ofrece login a invitados, sin peticiones privadas ni altas', async () => {
        const { router, adapter } = await start('/characters', { guest: true });
        expect(favoriteButton()).toBeNull();
        const link = [...document.querySelectorAll('article a')].find((item) => item.textContent.includes('Inicia sesión'));
        expect(link.getAttribute('href')).toBe('/login?redirect=/characters');
        await router.push('/favorites');
        await flushPromises();
        expect(router.currentRoute.value.name).toBe('login');
        expect(adapter.mock.calls.some(([config]) => config.url.startsWith('/favorites'))).toBe(false);
    });

    it('muestra carga y no permite marcar antes de conocer la colección completa', async () => {
        const pending = deferred();
        await start('/characters', { listFailure: (config) => pending.promise.then(() => response(config, characterPage({ perPage: 100 }))) });
        expect(favoriteButton().disabled).toBe(true);
        expect(document.body.textContent).toContain('Cargando favoritos…');
        pending.resolve();
        await flushPromises();
        expect(favoriteButton().getAttribute('aria-pressed')).toBe('true');
    });

    it('ajusta la última página tras eliminar su único elemento y recupera el foco', async () => {
        const data = Array.from({ length: 21 }, (_, i) => ({ ...character, id: i + 1, name: `Personaje ${i + 1}` }));
        const { router } = await start('/favorites?page=2', { data });
        expect(document.querySelectorAll('article')).toHaveLength(1);
        expect(document.querySelector('article h2').textContent).toBe('Personaje 21');
        favoriteButton().click();
        await vi.waitFor(() => expect(router.currentRoute.value.fullPath).toBe('/favorites'));
        await flushPromises();
        expect(document.querySelectorAll('article')).toHaveLength(20);
        expect(document.activeElement.matches('main,h1')).toBe(true);
        expect(button('Siguiente').disabled).toBe(true);
    });

    it('pagina sin nuevas peticiones y normaliza parámetros ajenos', async () => {
        const data = Array.from({ length: 23 }, (_, i) => ({ ...character, id: i + 1 }));
        const { router, adapter } = await start('/favorites?extra=1', { data });
        expect(router.currentRoute.value.fullPath).toBe('/favorites');
        button('Siguiente').click();
        await vi.waitFor(() => expect(router.currentRoute.value.query.page).toBe('2'));
        expect(document.querySelectorAll('article')).toHaveLength(3);
        router.back();
        await vi.waitFor(() => expect(document.querySelectorAll('article')).toHaveLength(20));
        expect(adapter.mock.calls.filter(([config]) => config.url === '/favorites')).toHaveLength(1);
    });

    it('desactiva los botones durante la escritura y evita el doble envío', async () => {
        const pending = deferred();
        const { adapter } = await start('/characters', { mutation: (config) => pending.promise.then(() => response(config, { data: character })) });
        favoriteButton().click();
        favoriteButton().click();
        await flushPromises();
        expect(favoriteButton().disabled).toBe(true);
        expect(favoriteButton().getAttribute('aria-pressed')).toBe('false');
        pending.resolve();
        await flushPromises();
        expect(adapter.mock.calls.filter(([config]) => config.method === 'put')).toHaveLength(1);
        expect(favoriteButton().disabled).toBe(false);
    });

    it('ofrece renovación explícita de CSRF sin repetir automáticamente el alta', async () => {
        const mutation = vi.fn().mockImplementationOnce((config) => rejectResponse(config, 419, 'csrf_error'))
            .mockImplementationOnce((config) => Promise.resolve(response(config, { data: character })));
        const { adapter } = await start('/characters', { mutation });
        favoriteButton().click();
        await flushPromises();
        expect(document.querySelector('[role=alert]').textContent).toContain('protección CSRF');
        expect(mutation).toHaveBeenCalledTimes(1);
        button('Renovar CSRF').click();
        await flushPromises();
        expect(favoriteButton().getAttribute('aria-pressed')).toBe('true');
        expect(adapter.mock.calls.filter(([config]) => config.url === '/auth/csrf-cookie')).toHaveLength(2);
    });

    it('abandona favoritos cuando una operación devuelve 401, sin datos privados residuales', async () => {
        const { router, session } = await start('/favorites', { data: [character], mutation: (config) => rejectResponse(config, 401, 'unauthenticated') });
        favoriteButton().click();
        await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('login'));
        expect(session.user.value).toBeNull();
        expect(router.currentRoute.value.query.redirect).toBe('/favorites');
        expect(document.querySelector('article')).toBeNull();
    });

    it('un 401 durante la carga inicial también caduca la sesión', async () => {
        const { router, session } = await start('/favorites', { listFailure: (config) => rejectResponse(config, 401, 'unauthenticated') });
        await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('login'));
        expect(session.status.value).toBe('guest');
    });

    it('muestra el error de carga y recupera favoritos con un reintento manual', async () => {
        const listFailure = vi.fn().mockImplementationOnce((config) => rejectResponse(config, 500, 'error'))
            .mockImplementationOnce((config) => Promise.resolve(response(config, characterPage({ perPage: 100 }))));
        await start('/favorites', { listFailure });
        expect(document.querySelector('[role=alert]')).not.toBeNull();
        expect(document.querySelector('article')).toBeNull();
        expect(listFailure).toHaveBeenCalledTimes(1);
        button('Reintentar favoritos').click();
        await flushPromises();
        expect(document.querySelector('article h2').textContent).toBe('Rick Sanchez');
    });
});
