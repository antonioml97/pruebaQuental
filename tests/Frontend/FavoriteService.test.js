import { describe, expect, it, vi } from 'vitest';
import { createFavoriteService } from '../../resources/js/domains/favorites';
import { createApiClient } from '../../resources/js/shared/services/http/createApiClient';
import { character, characterPage } from './support/characters';
import { rejectResponse, response } from './support/http';

describe('Transporte de favoritos', () => {
    it('consulta páginas de 100 con el cliente configurado y sin seguir enlaces externos', async () => {
        const payload = characterPage({ page: 2, perPage: 100, total: 101 });
        payload.links.first = 'https://otro.test/favorites';
        const adapter = vi.fn((config) => Promise.resolve(response(config, payload)));
        const signal = new AbortController().signal;
        const service = createFavoriteService(createApiClient({ adapter }));
        expect(await service.list(2, { signal })).toEqual(payload);
        expect(adapter.mock.calls[0][0]).toMatchObject({ url: '/favorites', method: 'get', params: { page: 2, per_page: 100 }, signal, withCredentials: true });
        expect(adapter).toHaveBeenCalledTimes(1);
    });

    it.each(['add', 'remove'])('prepara CSRF antes de %s, sin token en JSON', async (method) => {
        const adapter = vi.fn((config) => Promise.resolve(response(config, { data: character })));
        const service = createFavoriteService(createApiClient({ adapter }));
        const signal = new AbortController().signal;
        await service[method](1, { signal });
        expect(adapter.mock.calls.map(([config]) => [config.method, config.url])).toEqual([
            ['get', '/auth/csrf-cookie'], [method === 'add' ? 'put' : 'delete', '/favorites/1'],
        ]);
        expect(adapter.mock.calls[1][0]).toMatchObject({ signal, withCredentials: true, withXSRFToken: true });
        expect(adapter.mock.calls[1][0].data).toBeUndefined();
    });

    it.each([0, -1, 1.5, '1', '../auth/user', Number.MAX_SAFE_INTEGER + 1])('rechaza el ID %j antes de preparar CSRF', async (id) => {
        const client = { get: vi.fn() };
        await expect(createFavoriteService(client).add(id)).rejects.toMatchObject({ code: 'invalid_character' });
        await expect(createFavoriteService(client).remove(id)).rejects.toMatchObject({ code: 'invalid_character' });
        expect(client.get).not.toHaveBeenCalled();
    });

    it.each([{}, characterPage(), characterPage({ page: 2, perPage: 100 })])('rechaza respuestas de listado inválidas o de otra página', async (data) => {
        await expect(createFavoriteService({ get: async () => ({ data }) }).list(1)).rejects.toMatchObject({ code: 'invalid_response' });
    });

    it('rechaza un alta con el personaje incorrecto', async () => {
        const client = { get: async () => ({}), put: async () => ({ data: { data: { ...character, id: 2 } } }) };
        await expect(createFavoriteService(client).add(1)).rejects.toMatchObject({ code: 'invalid_response' });
    });

    it.each([401, 419, 500])('no repite la mutación ante %i', async (status) => {
        const adapter = vi.fn((config) => config.method === 'get'
            ? Promise.resolve(response(config, '', 204)) : rejectResponse(config, status, 'favorite_error'));
        await expect(createFavoriteService(createApiClient({ adapter })).add(1)).rejects.toMatchObject({ status });
        expect(adapter).toHaveBeenCalledTimes(2);
    });

    it('no escribe si falla la preparación CSRF', async () => {
        const adapter = vi.fn((config) => rejectResponse(config, 500, 'error'));
        await expect(createFavoriteService(createApiClient({ adapter })).remove(1)).rejects.toMatchObject({ status: 500 });
        expect(adapter).toHaveBeenCalledTimes(1);
    });
});
