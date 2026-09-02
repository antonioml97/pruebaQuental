import { describe, expect, it, vi } from 'vitest';
import { createApiClient } from '../../resources/js/shared/services/http/createApiClient';
import { createAuthenticationService } from '../../resources/js/domains/authentication';
import { isRequestCancelled } from '../../resources/js/shared/services/http/ApiError';
import { rejectResponse, response } from './support/http';

const user = { id: 1, name: 'Morty', email: 'morty@example.test' };
const credentials = { name: 'Morty', email: user.email, password: 'Prueba123', password_confirmation: 'Prueba123' };

describe('Servicio de autenticación', () => {
    it.each(['login', 'register'])('prepara CSRF antes de %s y entrega solo el usuario público', async (operation) => {
        const adapter = vi.fn(async (config) => response(config, config.method === 'get' ? {} : {
            data: { user: { ...user, internal_field: 'no-publicar' }, expires_at: '2026-10-01T10:00:00Z' },
        }));
        const auth = createAuthenticationService(createApiClient({ adapter }));
        expect(await auth[operation](credentials)).toEqual(user);
        expect(adapter.mock.calls.map(([config]) => [config.method, config.url])).toEqual([
            ['get', '/auth/csrf-cookie'], ['post', `/auth/${operation}`],
        ]);
        const sent = JSON.parse(adapter.mock.calls[1][0].data);
        expect(sent).toEqual(operation === 'login' ? { email: user.email, password: credentials.password } : credentials);
    });

    it('restaura el usuario con GET sin preparar CSRF ni guardar campos adicionales', async () => {
        const adapter = vi.fn(async (config) => response(config, { data: { ...user, internal_field: 'no-publicar' } }));
        const auth = createAuthenticationService(createApiClient({ adapter }));
        expect(await auth.currentUser()).toEqual(user);
        expect(adapter).toHaveBeenCalledTimes(1);
        expect(adapter.mock.calls[0][0].url).toBe('/auth/user');
    });

    it('prepara CSRF y acepta el 204 de logout', async () => {
        const adapter = vi.fn(async (config) => response(config, '', 204));
        const auth = createAuthenticationService(createApiClient({ adapter }));
        expect(await auth.logout()).toBeUndefined();
        expect(adapter.mock.calls.map(([config]) => config.url)).toEqual(['/auth/csrf-cookie', '/auth/logout']);
    });

    it('no envía la mutación si falla la preparación de CSRF', async () => {
        const adapter = vi.fn((config) => rejectResponse(config, 503, 'unavailable'));
        const auth = createAuthenticationService(createApiClient({ adapter }));
        await expect(auth.login(credentials)).rejects.toMatchObject({ status: 503 });
        expect(adapter).toHaveBeenCalledTimes(1);
    });

    it('no reenvía automáticamente un login rechazado por CSRF', async () => {
        const adapter = vi.fn((config) => config.method === 'get'
            ? Promise.resolve(response(config, {}, 204)) : rejectResponse(config, 419, 'csrf_token_mismatch'));
        const auth = createAuthenticationService(createApiClient({ adapter }));
        await expect(auth.login(credentials)).rejects.toMatchObject({ code: 'csrf_token_mismatch', status: 419 });
        expect(adapter).toHaveBeenCalledTimes(2);
    });

    it('permite cancelar entre CSRF y la mutación', async () => {
        const controller = new AbortController();
        const adapter = vi.fn(async (config) => {
            controller.abort();
            return response(config, {}, 204);
        });
        const auth = createAuthenticationService(createApiClient({ adapter }));
        const failure = await auth.register(credentials, { signal: controller.signal }).catch((error) => error);
        expect(isRequestCancelled(failure)).toBe(true);
        expect(adapter).toHaveBeenCalledTimes(1);
    });

    it.each([null, {}, { id: 0, name: 'Morty', email: 'm@e.test' }])('rechaza una identidad inválida: %j', async (data) => {
        const auth = createAuthenticationService(createApiClient({ adapter: async (config) => response(config, { data }) }));
        await expect(auth.currentUser()).rejects.toMatchObject({ code: 'invalid_response' });
    });
});
