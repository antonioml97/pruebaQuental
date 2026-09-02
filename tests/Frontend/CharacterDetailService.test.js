import { describe, expect, it, vi } from 'vitest';
import { createCharacterService } from '../../resources/js/domains/characters';
import { createApiClient } from '../../resources/js/shared/services/http/createApiClient';
import { characterDetail } from './support/characters';
import { rejectResponse, response } from './support/http';

describe('Servicio de detalle del personaje', () => {
    it('consulta el identificador público por el cliente configurado y permite cancelar', async () => {
        const data = characterDetail({ id: 42 });
        const adapter = vi.fn((config) => Promise.resolve(response(config, { data })));
        const controller = new AbortController();
        const result = await createCharacterService(createApiClient({ adapter })).detail('42', { signal: controller.signal });
        expect(result).toEqual(data);
        expect(adapter).toHaveBeenCalledTimes(1);
        expect(adapter.mock.calls[0][0]).toMatchObject({ url: '/characters/42', method: 'get', signal: controller.signal });
        expect(adapter.mock.calls[0][0].params).toBeUndefined();
    });

    it('acepta relaciones ausentes y una lista vacía de episodios', async () => {
        const data = characterDetail({ origin: null, current_location: null, episodes: [] });
        const service = createCharacterService({ get: async () => ({ data: { data } }) });
        expect(await service.detail(1)).toEqual(data);
    });

    it.each([0, -1, 1.2, NaN, Infinity, true, '0', '-1', '01', '1.2', '1e2', '../auth/user', 'https://otro.test', '9007199254740992', '', null, undefined, [1], {}])('rechaza el identificador inválido %j antes de consultar', async (id) => {
        const get = vi.fn();
        await expect(createCharacterService({ get }).detail(id)).rejects.toMatchObject({ status: 404, code: 'character_not_found' });
        expect(get).not.toHaveBeenCalled();
    });

    it.each([Number.MAX_SAFE_INTEGER, String(Number.MAX_SAFE_INTEGER)])('acepta el límite seguro como número o texto: %s', async (id) => {
        const data = characterDetail({ id: Number(id) });
        const get = vi.fn(async () => ({ data: { data } }));
        expect(await createCharacterService({ get }).detail(id)).toEqual(data);
        expect(get).toHaveBeenCalledWith(`/characters/${id}`, { signal: undefined });
    });

    it.each([
        {}, { data: null }, { data: characterDetail({ id: 2 }) },
        { data: characterDetail({ origin: {} }) }, { data: characterDetail({ current_location: undefined }) },
        { data: characterDetail({ episodes: null }) }, { data: characterDetail({ episodes: [{ id: 1 }] }) },
        { data: characterDetail({ name: null }) },
    ])('rechaza fichas incompletas o pertenecientes a otro personaje', async (payload) => {
        const service = createCharacterService({ get: async () => ({ data: payload }) });
        await expect(service.detail('1')).rejects.toMatchObject({ code: 'invalid_response' });
    });

    it.each([404, 500])('propaga el error %i sin reintentos automáticos', async (status) => {
        const adapter = vi.fn((config) => rejectResponse(config, status, 'character_error'));
        await expect(createCharacterService(createApiClient({ adapter })).detail('1')).rejects.toMatchObject({ status });
        expect(adapter).toHaveBeenCalledTimes(1);
    });
});
