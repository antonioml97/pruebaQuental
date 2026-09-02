import { afterEach, describe, expect, it, vi } from 'vitest';
import { flushPromises } from '@vue/test-utils';
import { createFavorites } from '../../resources/js/domains/favorites';
import { createSession } from '../../resources/js/shared/composables/useSession';
import { ApiError } from '../../resources/js/shared/services/http/ApiError';
import { character, characterPage } from './support/characters';
import { deferred } from './support/http';

const user = { id: 1, name: 'Morty', email: 'morty@example.test' };
const stores = [];
afterEach(() => { stores.splice(0).forEach((store) => store.dispose()); });

async function setup(overrides = {}, authenticated = true) {
    const session = createSession({ currentUser: async () => authenticated ? user : null, login: async (identity) => identity, logout: async () => null });
    await session.restore();
    const service = {
        list: vi.fn(async () => characterPage({ data: [], perPage: 100 })),
        add: vi.fn(async () => character), remove: vi.fn(async () => {}), ...overrides,
    };
    const favorites = createFavorites(service, session);
    stores.push(favorites);
    return { session, favorites, service };
}

describe('Favoritos compartidos y aislamiento de sesión', () => {
    it('no consulta ni escribe favoritos de un invitado', async () => {
        const { favorites, service } = await setup({}, false);
        await favorites.add(character);
        await favorites.remove(character);
        await favorites.retry();
        expect(service.list).not.toHaveBeenCalled();
        expect(service.add).not.toHaveBeenCalled();
        expect(service.remove).not.toHaveBeenCalled();
    });

    it('reúne todas las páginas antes de dar por conocido el estado y no duplica personajes', async () => {
        const next = deferred();
        const list = vi.fn().mockResolvedValueOnce(characterPage({ perPage: 100, total: 101 })).mockImplementationOnce(() => next.promise);
        const { favorites } = await setup({ list });
        await flushPromises();
        expect(favorites.loading.value).toBe(true);
        expect(favorites.loaded.value).toBe(false);
        expect(favorites.items.value).toEqual([]);
        next.resolve(characterPage({ data: [character, { ...character, id: 101 }], page: 2, perPage: 100, total: 101 }));
        await flushPromises();
        expect(favorites.items.value.map((item) => item.id)).toEqual([1, 101]);
        expect(list.mock.calls.map(([page]) => page)).toEqual([1, 2]);
        expect(favorites.has(101)).toBe(true);
    });

    it('confirma alta y baja, sin optimismo ni envíos simultáneos', async () => {
        const pending = deferred();
        const { favorites, service } = await setup({ add: vi.fn(() => pending.promise) });
        await flushPromises();
        const saving = favorites.add(character);
        await favorites.add(character);
        await favorites.add({ ...character, id: 2 });
        await favorites.remove(character);
        expect(service.remove).not.toHaveBeenCalled();
        expect(service.add).toHaveBeenCalledTimes(1);
        expect(favorites.has(1)).toBe(false);
        expect(favorites.disabled.value).toBe(true);
        pending.resolve(character);
        await saving;
        expect(favorites.has(1)).toBe(true);
        expect(favorites.notice.value).toContain('añadido');
        await favorites.remove(character);
        expect(favorites.has(1)).toBe(false);
        expect(favorites.notice.value).toContain('eliminado');
    });

    it('mantiene una sola relación aunque se confirme otra alta idempotente', async () => {
        const { favorites } = await setup();
        await flushPromises();
        await favorites.add(character);
        await favorites.add(character);
        expect(favorites.items.value).toHaveLength(1);
    });

    it.each([419, 500])('mantiene los datos ante %i y reintenta solo al solicitarlo', async (status) => {
        const add = vi.fn().mockRejectedValueOnce(new ApiError({ status, message: 'Error temporal' })).mockResolvedValueOnce(character);
        const { favorites, session } = await setup({ add });
        await flushPromises();
        await favorites.add(character);
        expect(favorites.error.value.status).toBe(status);
        expect(favorites.has(1)).toBe(false);
        expect(session.isAuthenticated.value).toBe(true);
        expect(add).toHaveBeenCalledTimes(1);
        await favorites.retry();
        expect(favorites.has(1)).toBe(true);
        expect(favorites.error.value).toBeNull();
    });

    it.each([419, 500])('conserva el favorito ante un error %i de baja y reintenta la eliminación', async (status) => {
        const remove = vi.fn().mockRejectedValueOnce(new ApiError({ status, message: 'Error temporal' })).mockResolvedValueOnce(undefined);
        const { favorites, session, service } = await setup({
            list: vi.fn(async () => characterPage({ perPage: 100 })), remove,
        });
        await flushPromises();
        await favorites.remove(character);
        expect(favorites.has(1)).toBe(true);
        expect(favorites.error.value.action.type).toBe('remove');
        expect(session.isAuthenticated.value).toBe(true);
        expect(remove).toHaveBeenCalledTimes(1);

        await favorites.retry();
        expect(remove).toHaveBeenCalledTimes(2);
        expect(service.add).not.toHaveBeenCalled();
        expect(favorites.has(1)).toBe(false);
        expect(favorites.error.value).toBeNull();
    });

    it('espera la baja y bloquea tanto altas como bajas mientras está pendiente', async () => {
        const pending = deferred();
        const { favorites, service } = await setup({
            list: vi.fn(async () => characterPage({ perPage: 100 })),
            remove: vi.fn(() => pending.promise),
        });
        await flushPromises();
        const saving = favorites.remove(character);
        await favorites.remove(character);
        await favorites.add(character);
        expect(service.remove).toHaveBeenCalledTimes(1);
        expect(service.add).not.toHaveBeenCalled();
        expect(favorites.has(1)).toBe(true);
        expect(favorites.disabled.value).toBe(true);

        pending.resolve();
        await saving;
        expect(favorites.has(1)).toBe(false);
        expect(favorites.saving.value).toBe(false);
    });

    it('un listado fallido no habilita mutaciones y puede reintentarse', async () => {
        const list = vi.fn().mockRejectedValueOnce(new ApiError({ status: 500, message: 'Fallo de carga' })).mockResolvedValueOnce(characterPage({ perPage: 100 }));
        const { favorites, service } = await setup({ list });
        await flushPromises();
        expect(favorites.loaded.value).toBe(false);
        await favorites.add(character);
        await favorites.remove(character);
        expect(service.add).not.toHaveBeenCalled();
        expect(service.remove).not.toHaveBeenCalled();
        await favorites.retry();
        expect(favorites.has(1)).toBe(true);
    });

    it.each(['list', 'add', 'remove'])('un 401 de %s caduca la sesión y borra los datos privados', async (operation) => {
        const { favorites, session, service } = await setup();
        await flushPromises();
        service[operation].mockRejectedValue(new ApiError({ status: 401 }));
        if (operation === 'list') await favorites.retry();
        else await favorites[operation](character);
        expect(session.status.value).toBe('guest');
        expect(favorites.items.value).toEqual([]);
        expect(favorites.loaded.value).toBe(false);
    });

    it.each(['success', '401'])('descarta una lectura antigua (%s) tras cambiar de cuenta', async (outcome) => {
        const old = deferred();
        const list = vi.fn().mockImplementationOnce(() => old.promise).mockResolvedValueOnce(characterPage({ data: [{ ...character, id: 2 }], perPage: 100 }));
        const { favorites, session } = await setup({ list });
        await session.login({ ...user, id: 2 });
        await flushPromises();
        expect(list.mock.calls[0][1].signal.aborted).toBe(true);
        if (outcome === 'success') old.resolve(characterPage({ perPage: 100 }));
        else old.reject(new ApiError({ status: 401 }));
        await flushPromises();
        expect(session.user.value.id).toBe(2);
        expect(favorites.items.value.map((item) => item.id)).toEqual([2]);
    });

    it.each(['add', 'remove'])('descarta una escritura antigua (%s) al cerrar sesión e iniciar otra', async (operation) => {
        const old = deferred();
        const { favorites, session } = await setup({ [operation]: vi.fn(() => old.promise) });
        await flushPromises();
        const saving = favorites[operation](character);
        await session.logout();
        expect(favorites.items.value).toEqual([]);
        await session.login({ ...user, id: 2 });
        await flushPromises();
        old.resolve(character);
        await saving;
        expect(favorites.items.value).toEqual([]);
        expect(favorites.notice.value).toBe('');
    });

    it('al desmontar cancela la carga y deja de observar la sesión', async () => {
        const pending = deferred();
        const list = vi.fn(() => pending.promise);
        const { favorites, session } = await setup({ list });
        favorites.dispose();
        expect(list.mock.calls[0][1].signal.aborted).toBe(true);
        pending.resolve(characterPage({ perPage: 100 }));
        await session.login({ ...user, id: 2 });
        await flushPromises();
        expect(list).toHaveBeenCalledTimes(1);
        expect(favorites.items.value).toEqual([]);
    });
});
