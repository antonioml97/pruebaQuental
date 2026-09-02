import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory } from 'vue-router';
import App from '../../resources/js/App.vue';
import { createAppRouter } from '../../resources/js/router';
import { createSession, sessionKey } from '../../resources/js/shared/composables/useSession';
import { ApiError } from '../../resources/js/shared/services/http/ApiError';
import { characterServiceKey } from '../../resources/js/domains/characters';
import { emptyCharacterService } from './support/characters';
import { favoriteTestProvider } from './support/favorites';

enableAutoUnmount(afterEach);

beforeEach(() => vi.stubGlobal('scrollTo', vi.fn()));
afterEach(() => {
    vi.unstubAllEnvs();
    vi.unstubAllGlobals();
    document.body.replaceChildren();
});

async function mountApp(path = '/') {
    const router = createAppRouter(createMemoryHistory());
    await router.push(path);
    await router.isReady();
    const session = createSession({ currentUser: () => Promise.reject(new ApiError({ status: 401, code: 'unauthenticated', message: 'Invitado' })) });
    await session.restore();
    const wrapper = mount(App, { attachTo: document.body, global: { plugins: [router], provide: { ...favoriteTestProvider(), [sessionKey]: session, [characterServiceKey]: emptyCharacterService } } });
    return { wrapper, router };
}

describe('Navegación y layout de la SPA', () => {
    it.each([
        ['/', 'Explora el multiverso.', 'Personajes'],
        ['/characters', 'Explora el multiverso.', 'Personajes'],
        ['/characters/42', 'Personaje #42', 'Detalle del personaje'],
        ['/login', 'Iniciar sesión', 'Iniciar sesión'],
        ['/register', 'Crear cuenta', 'Crear cuenta'],
        ['/favorites', 'Tus favoritos', 'Favoritos'],
        ['/portal/desconocido', 'Este portal no lleva a ninguna parte.', 'Página no encontrada'],
    ])('muestra la vista y el título de %s', async (path, heading, title) => {
        const warn = vi.spyOn(console, 'warn');
        const error = vi.spyOn(console, 'error');
        const { wrapper } = await mountApp(path);

        expect(wrapper.get('main h1').text()).toBe(heading);
        expect(document.title).toContain(`${title} · `);
        expect(wrapper.get('section').attributes('aria-labelledby')).toBe(wrapper.get('h1').attributes('id'));
        expect(warn).not.toHaveBeenCalled();
        expect(error).not.toHaveBeenCalled();
    });

    it('enlaza a Swagger mediante el servidor sin montar su interfaz en Vue', async () => {
        const { wrapper } = await mountApp();
        expect(wrapper.get('footer a').attributes('href')).toBe('/docs');
        expect(wrapper.find('#swagger-ui').exists()).toBe(false);
    });

    it.each([['Mi catálogo', 'Mi catálogo'], ['  ', 'Rick and Morty']])('respeta el nombre público %s', async (name, expected) => {
        vi.stubEnv('VITE_APP_NAME', name);
        const { wrapper } = await mountApp();
        expect(wrapper.get('header').text()).toContain(expected);
        expect(document.title).toBe(`Personajes · ${expected}`);
    });

    it('actualiza el enlace activo, el contenido, el título y el foco al navegar', async () => {
        const { wrapper, router } = await mountApp('/characters');
        expect(wrapper.get('nav a[aria-current="page"]').text()).toBe('Personajes');
        await router.push('/register');
        await flushPromises();
        expect(wrapper.get('nav a[aria-current="page"]').text()).toBe('Crear cuenta');
        expect(wrapper.get('h1').text()).toBe('Crear cuenta');
        expect(document.title).toContain('Crear cuenta ·');
        expect(document.activeElement).toBe(wrapper.get('main').element);
    });

    it('permite saltar al contenido sin recorrer la navegación', async () => {
        const { wrapper } = await mountApp();
        await wrapper.get('a[href="#main-content"]').trigger('click');
        expect(document.activeElement).toBe(wrapper.get('main').element);
        expect(wrapper.get('main').attributes('tabindex')).toBe('-1');
    });

    it('abre el menú y lo cierra con Escape devolviendo el foco al botón', async () => {
        const { wrapper } = await mountApp();
        const button = wrapper.get('button');
        const nav = wrapper.get('nav');
        expect(button.attributes('aria-controls')).toBe(nav.attributes('id'));
        expect(button.attributes('aria-expanded')).toBe('false');
        expect(nav.classes()).toContain('hidden');
        await button.trigger('click');
        expect(button.attributes('aria-expanded')).toBe('true');
        expect(nav.classes()).not.toContain('hidden');
        await nav.trigger('keydown', { key: 'Escape' });
        expect(button.attributes('aria-expanded')).toBe('false');
        expect(document.activeElement).toBe(button.element);
    });

    it.each([
        ['/favorites', 'Tus favoritos'],
        ['/characters', 'Explora el multiverso.'],
    ])('cierra el menú al seguir %s, aunque sea la página actual', async (path, heading) => {
        const { wrapper } = await mountApp();
        await wrapper.get('button').trigger('click');
        await wrapper.get(`nav a[href="${path}"]`).trigger('click');
        await flushPromises();
        expect(wrapper.get('h1').text()).toBe(heading);
        expect(wrapper.get('button').attributes('aria-expanded')).toBe('false');
        expect(document.activeElement).toBe(wrapper.get('main').element);
    });

    it('actualiza el identificador al reutilizar el detalle', async () => {
        const { wrapper, router } = await mountApp('/characters/1');
        await router.push('/characters/2');
        await flushPromises();
        expect(wrapper.get('h1').text()).toBe('Personaje #2');
    });

    it('permite volver al catálogo desde la página 404', async () => {
        const { wrapper } = await mountApp('/no-existe');
        await wrapper.get('main a').trigger('click');
        await flushPromises();
        expect(wrapper.get('h1').text()).toBe('Explora el multiverso.');
    });
});
