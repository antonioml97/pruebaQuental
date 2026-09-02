import { describe, expect, it, vi } from 'vitest';
import { createCharacterService } from '../../resources/js/domains/characters';
import { readCharacterQuery, writeCharacterQuery } from '../../resources/js/domains/characters/services/characterQuery';
import { createApiClient } from '../../resources/js/shared/services/http/createApiClient';
import { characterPage } from './support/characters';
import { rejectResponse, response } from './support/http';

describe('Consulta del catálogo', () => {
    it('normaliza filtros y elimina parámetros ajenos, repetidos y fuera de contrato', () => {
        expect(readCharacterQuery({ name: ['Rick', 'Morty'], status: 'alive', species: ' Human ', gender: 'invalid', page: '-2', per_page: '101', token: 'no' }))
            .toEqual({ name: '', status: '', species: 'Human', gender: '', page: 1, per_page: 20 });
        expect(writeCharacterQuery(readCharacterQuery({ page: '01', per_page: '20', name: ' Rick ' }))).toEqual({ name: 'Rick' });
    });

    it.each(['0', '-1', '1.2', 'abc', '1e2', ['2'], '9007199254740992'])('rechaza la página inválida %j', (page) => {
        expect(readCharacterQuery({ page }).page).toBe(1);
    });

    it('conserva los valores exactos del contrato y limita los textos', () => {
        const query = readCharacterQuery({ name: 'a'.repeat(300), species: 'b'.repeat(300), status: 'unknown', gender: 'Genderless', page: '3', per_page: '7' });
        expect(query.name).toHaveLength(255);
        expect(query.species).toHaveLength(255);
        expect(writeCharacterQuery(query)).toMatchObject({ status: 'unknown', gender: 'Genderless', page: '3', per_page: '7' });
    });

    it('consulta solo el endpoint del catálogo con parámetros y señal de cancelación', async () => {
        const payload = characterPage();
        const adapter = vi.fn((config) => Promise.resolve(response(config, payload)));
        const service = createCharacterService(createApiClient({ adapter }));
        const controller = new AbortController();
        expect(await service.list({ name: ' Rick ', status: 'Alive', gender: 'Male', species: 'Human', page: 2, per_page: 7, extra: 'no' }, { signal: controller.signal })).toEqual(payload);
        expect(adapter).toHaveBeenCalledTimes(1);
        expect(adapter.mock.calls[0][0]).toMatchObject({ url: '/characters', method: 'get', signal: controller.signal,
            params: { name: 'Rick', status: 'Alive', gender: 'Male', species: 'Human', page: 2, per_page: 7 } });
    });

    it.each([{}, { data: [] }, { ...characterPage(), data: [{ id: 1 }] }, { ...characterPage(), meta: { current_page: -1 } }])('rechaza respuestas incompletas sin romper la vista', async (payload) => {
        const service = createCharacterService(createApiClient({ adapter: (config) => Promise.resolve(response(config, payload)) }));
        await expect(service.list({})).rejects.toMatchObject({ code: 'invalid_response' });
    });

    it('propaga los errores de validación para mostrarlos sin reintentos automáticos', async () => {
        const adapter = vi.fn((config) => rejectResponse(config, 422, 'validation_error', { name: ['Nombre inválido.'] }));
        await expect(createCharacterService(createApiClient({ adapter })).list({})).rejects.toMatchObject({ status: 422, details: { name: ['Nombre inválido.'] } });
        expect(adapter).toHaveBeenCalledTimes(1);
    });
});
