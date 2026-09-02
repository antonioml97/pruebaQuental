import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory } from 'vue-router';
import App from '../../resources/js/App.vue';
import { createAppRouter } from '../../resources/js/router';
import { installSessionGuards } from '../../resources/js/router/sessionGuards';
import { createSession, sessionKey } from '../../resources/js/composables/useSession';
import { createApiClient } from '../../resources/js/services/http/createApiClient';
import { createAuthenticationService } from '../../resources/js/services/authentication/createAuthenticationService';
import { deferred, rejectResponse, response } from './support/http';

enableAutoUnmount(afterEach);
beforeEach(() => vi.stubGlobal('scrollTo', vi.fn()));
afterEach(() => { vi.unstubAllGlobals(); document.body.replaceChildren(); });
const user = { id: 1, name: 'Morty', email: 'morty@example.test' };

async function setup(path = '/login', { authenticated = false, mutation } = {}) {
    const adapter = vi.fn((config) => {
        if (config.url === '/auth/user') return authenticated
            ? Promise.resolve(response(config, { data: user })) : rejectResponse(config, 401, 'unauthenticated');
        if (config.url === '/auth/csrf-cookie') return Promise.resolve(response(config, '', 204));
        if (mutation) return mutation(config);
        return Promise.resolve(response(config, config.url === '/auth/logout' ? '' : { data: { user } }, config.url === '/auth/logout' ? 204 : 200));
    });
    const session = createSession(createAuthenticationService(createApiClient({ adapter })));
    await session.restore();
    const router = createAppRouter(createMemoryHistory());
    installSessionGuards(router, session);
    await router.push(path);
    const wrapper = mount(App, { attachTo: document.body, global: { plugins: [router], provide: { [sessionKey]: session } } });
    return { wrapper, router, session, adapter };
}

async function fill(wrapper, registration = false) {
    if (registration) await wrapper.get('[name="name"]').setValue('Morty');
    await wrapper.get('[name="email"]').setValue(user.email);
    await wrapper.get('[name="password"]').setValue('Prueba123');
    if (registration) await wrapper.get('[name="password_confirmation"]').setValue('Prueba123');
}

describe('Formularios de acceso', () => {
    it.each(['/login', '/register'])('asocia etiquetas, autocompletado y envío a los campos en %s', async (path) => {
        const { wrapper } = await setup(path);
        for (const input of wrapper.findAll('input')) {
            expect(wrapper.get(`label[for="${input.attributes('id')}"]`).text()).not.toBe('');
            expect(input.attributes('autocomplete')).toBeTruthy();
            expect(input.attributes('required')).toBeDefined();
        }
        expect(wrapper.get('[name="password"]').attributes('autocomplete')).toBe(path === '/login' ? 'current-password' : 'new-password');
    });

    it('valida campos vacíos sin enviar, anuncia los errores y enfoca el resumen', async () => {
        const { wrapper, adapter } = await setup();
        await wrapper.get('form').trigger('submit');
        await flushPromises();
        expect(adapter).toHaveBeenCalledTimes(1);
        expect(wrapper.get('[role="alert"]').text()).toContain('Revisa los campos');
        expect(document.activeElement).toBe(wrapper.get('[role="alert"]').element);
        expect(wrapper.get('[name="email"]').attributes('aria-invalid')).toBe('true');
        expect(wrapper.get('[name="email"]').attributes('aria-describedby')).toContain('error-email');
    });

    it('valida la confirmación y la longitud básica del registro', async () => {
        const { wrapper, adapter } = await setup('/register');
        await fill(wrapper, true);
        await wrapper.get('[name="password"]').setValue('corta');
        await wrapper.get('form').trigger('submit');
        expect(wrapper.get('#error-password').text()).toContain('8 caracteres');
        expect(wrapper.get('#error-password_confirmation').text()).toContain('no coinciden');
        expect(adapter).toHaveBeenCalledTimes(1);
    });

    it('registra, inicia sesión y redirige al catálogo aunque la URL incluya otro destino', async () => {
        const { wrapper, router, session } = await setup('/register?redirect=/favorites');
        await fill(wrapper, true);
        await wrapper.get('form').trigger('submit');
        await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('characters'));
        expect(session.user.value).toEqual(user);
        expect(wrapper.get('nav').text()).toContain('Hola, Morty');
        expect(wrapper.find('nav a[href="/login"]').exists()).toBe(false);
    });

    it('entra desde favoritos, recupera el destino original y enfoca el contenido', async () => {
        const { wrapper, router, session } = await setup('/favorites?page=2');
        expect(router.currentRoute.value.name).toBe('login');
        await fill(wrapper);
        await wrapper.get('form').trigger('submit');
        await vi.waitFor(() => expect(router.currentRoute.value.fullPath).toBe('/favorites?page=2'));
        expect(session.isAuthenticated.value).toBe(true);
        expect(document.activeElement).toBe(wrapper.get('main').element);
    });

    it('no utiliza una redirección externa después del login', async () => {
        const { wrapper, router } = await setup('/login?redirect=https://otro.test');
        await fill(wrapper);
        await wrapper.get('form').trigger('submit');
        await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('characters'));
    });

    it('impide dobles envíos y abandonar una escritura en curso', async () => {
        const request = deferred();
        const { wrapper, router, adapter } = await setup('/login', { mutation: (config) => request.promise.then(() => response(config, { data: { user } })) });
        await fill(wrapper);
        await wrapper.get('form').trigger('submit');
        await wrapper.get('form').trigger('submit');
        await flushPromises();
        expect(wrapper.get('fieldset').attributes('disabled')).toBeDefined();
        expect(wrapper.get('form').attributes('aria-busy')).toBe('true');
        expect(wrapper.get('section > [role="status"]').text()).toContain('Espera');
        expect(adapter.mock.calls.filter(([config]) => config.method === 'post')).toHaveLength(1);
        await router.push('/characters');
        expect(router.currentRoute.value.name).toBe('login');
        request.resolve();
        await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('characters'));
    });

    it.each([401, 422, 429, 500])('presenta los errores %i sin perder el correo ni redirigir', async (status) => {
        const { wrapper, router, adapter } = await setup('/login', { mutation: (config) => rejectResponse(config, status, 'error', status === 422 ? { email: ['El correo no es válido.'] } : {}) });
        await fill(wrapper);
        await wrapper.get('form').trigger('submit');
        await flushPromises();
        expect(wrapper.get('[role="alert"]').text()).toContain('Mensaje del servidor.');
        expect(wrapper.get('[name="email"]').element.value).toBe(user.email);
        expect(router.currentRoute.value.name).toBe('login');
        expect(wrapper.get('fieldset').attributes('disabled')).toBeUndefined();
        expect(adapter.mock.calls.filter(([config]) => config.method === 'post')).toHaveLength(1);
        if (status === 422) expect(wrapper.get('#error-email').text()).toBe('El correo no es válido.');
    });

    it('el 419 permite un reintento explícito con una nueva preparación CSRF', async () => {
        let attempts = 0;
        const { wrapper, router, adapter } = await setup('/login', { mutation: (config) => ++attempts === 1
            ? rejectResponse(config, 419, 'csrf_token_mismatch') : Promise.resolve(response(config, { data: { user } })) });
        await fill(wrapper);
        await wrapper.get('form').trigger('submit');
        await flushPromises();
        expect(attempts).toBe(1);
        expect(wrapper.get('button[type="submit"]').text()).toBe('Renovar CSRF y reintentar');
        await wrapper.get('form').trigger('submit');
        await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('characters'));
        expect(attempts).toBe(2);
        expect(adapter.mock.calls.filter(([config]) => config.url === '/auth/csrf-cookie')).toHaveLength(2);
    });
});

describe('Cierre de sesión desde el layout', () => {
    it('revoca la sesión, abandona favoritos y actualiza la navegación', async () => {
        const { wrapper, router, session, adapter } = await setup('/favorites', { authenticated: true });
        await wrapper.get('nav button').trigger('click');
        await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('characters'));
        expect(session.status.value).toBe('guest');
        expect(wrapper.find('nav button').exists()).toBe(false);
        expect(wrapper.get('nav a[href="/login"]').text()).toBe('Iniciar sesión');
        expect(adapter.mock.calls.map(([config]) => config.url)).toEqual(['/auth/user', '/auth/csrf-cookie', '/auth/logout']);
        expect(document.activeElement).toBe(wrapper.get('main').element);
    });

    it('no borra la identidad si logout falla y permite recuperar CSRF manualmente', async () => {
        let attempts = 0;
        const { wrapper, session, adapter } = await setup('/favorites', { authenticated: true, mutation: (config) => ++attempts === 1
            ? rejectResponse(config, 419, 'csrf_token_mismatch') : Promise.resolve(response(config, '', 204)) });
        await wrapper.get('nav button').trigger('click');
        await flushPromises();
        expect(session.isAuthenticated.value).toBe(true);
        expect(wrapper.get('[role="alert"]').text()).toContain('No se ha cerrado');
        expect(document.activeElement).toBe(wrapper.get('[role="alert"]').element);
        expect(attempts).toBe(1);
        await wrapper.get('[role="alert"] button').trigger('click');
        await vi.waitFor(() => expect(session.status.value).toBe('guest'));
        expect(adapter.mock.calls.filter(([config]) => config.url === '/auth/csrf-cookie')).toHaveLength(2);
    });

    it('trata el logout de una sesión ya caducada como cierre correcto', async () => {
        const { wrapper, session, router } = await setup('/favorites', { authenticated: true, mutation: (config) => rejectResponse(config, 401, 'unauthenticated') });
        await wrapper.get('nav button').trigger('click');
        await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('characters'));
        expect(session.status.value).toBe('guest');
    });
});
