import { describe, expect, it, vi } from 'vitest';
import { isReadonly } from 'vue';
import { CanceledError } from 'axios';
import { createSession } from '../../resources/js/composables/useSession';
import { ApiError } from '../../resources/js/services/http/ApiError';
import { deferred } from './support/http';

const user = { id: 1, name: 'Morty', email: 'morty@example.test' };
const failure = (status, code = 'error', details = {}) => new ApiError({ status, code, details, message: 'Mensaje público.' });

function service() {
    return { currentUser: vi.fn().mockResolvedValue(user), login: vi.fn().mockResolvedValue(user), register: vi.fn().mockResolvedValue(user), logout: vi.fn().mockResolvedValue(undefined) };
}

describe('Estado reactivo de sesión', () => {
    it('restaura la identidad y comparte una única petición pendiente', async () => {
        const request = deferred();
        const auth = service();
        auth.currentUser.mockReturnValue(request.promise);
        const session = createSession(auth);
        expect(session.status.value).toBe('loading');
        expect(session.user.value).toBeNull();
        const first = session.restore();
        expect(session.restore()).toBe(first);
        request.resolve(user);
        expect(isReadonly(await first)).toBe(true);
        expect(auth.currentUser).toHaveBeenCalledTimes(1);
        expect(session.status.value).toBe('authenticated');
        expect(session.user.value).toEqual(user);
        expect(isReadonly(session.user)).toBe(true);
        expect(isReadonly(session.user.value)).toBe(true);
    });

    it('un 401 de restauración deja al usuario como invitado sin error ni bucles', async () => {
        const auth = service();
        const session = createSession(auth);
        await session.restore();
        auth.currentUser.mockRejectedValue(failure(401, 'unauthenticated'));
        expect(await session.restore()).toBeNull();
        expect(session.status.value).toBe('guest');
        expect(session.user.value).toBeNull();
        expect(session.error.value).toBeNull();
        expect(auth.currentUser).toHaveBeenCalledTimes(2);
    });

    it.each(['login', 'register'])('%s actualiza la identidad sin persistir credenciales', async (operation) => {
        const auth = service();
        const session = createSession(auth);
        const credentials = { email: user.email, password: 'no-guardar' };
        await session[operation](credentials);
        expect(session.isAuthenticated.value).toBe(true);
        expect(session.user.value).toEqual(user);
        expect(session.user.value).not.toHaveProperty('password');
        expect(auth[operation]).toHaveBeenCalledWith(credentials, undefined);
    });

    it('el 401 de login conserva el mensaje de credenciales incorrectas', async () => {
        const auth = service();
        auth.login.mockRejectedValue(failure(401, 'invalid_credentials'));
        const session = createSession(auth);
        await expect(session.login({})).rejects.toMatchObject({ status: 401 });
        expect(session.status.value).toBe('guest');
        expect(session.error.value.code).toBe('invalid_credentials');
    });

    it.each([419, 422, 500])('expone el error %i sin destruir la identidad ni repetir la operación', async (status) => {
        const auth = service();
        const session = createSession(auth);
        await session.restore();
        auth.login.mockRejectedValue(failure(status, 'validation_error', { email: ['Mensaje por campo.'] }));
        await expect(session.login({})).rejects.toMatchObject({ status });
        expect(session.status.value).toBe('authenticated');
        expect(session.user.value).toEqual(user);
        expect(session.error.value.details.email).toEqual(['Mensaje por campo.']);
        expect(auth.login).toHaveBeenCalledTimes(1);
    });

    it('permite reintentar explícitamente una restauración fallida', async () => {
        const auth = service();
        auth.currentUser.mockRejectedValueOnce(failure(null, 'network_error'));
        const session = createSession(auth);
        await expect(session.restore()).rejects.toMatchObject({ code: 'network_error' });
        expect(session.status.value).toBe('guest');
        expect(session.error.value.code).toBe('network_error');
        await session.restore();
        expect(session.error.value).toBeNull();
        expect(session.status.value).toBe('authenticated');
    });

    it('cancelar logout no equivale a cerrar la sesión', async () => {
        const auth = service();
        const session = createSession(auth);
        await session.restore();
        const canceled = new CanceledError();
        auth.logout.mockRejectedValue(canceled);
        await expect(session.logout()).rejects.toBe(canceled);
        expect(session.status.value).toBe('authenticated');
        expect(session.error.value).toBeNull();
    });

    it.each([204, 401])('logout deja la sesión como invitada ante %i', async (status) => {
        const auth = service();
        const session = createSession(auth);
        await session.restore();
        if (status === 401) auth.logout.mockRejectedValue(failure(401));
        expect(await session.logout()).toBeNull();
        expect(session.status.value).toBe('guest');
        expect(session.user.value).toBeNull();
    });

    it('no permite escrituras de sesión concurrentes que compitan por la cookie', async () => {
        const auth = service();
        const request = deferred();
        auth.login.mockReturnValue(request.promise);
        const session = createSession(auth);
        const login = session.login({});
        await expect(session.logout()).rejects.toMatchObject({ code: 'session_busy' });
        expect(auth.logout).not.toHaveBeenCalled();
        request.resolve(user);
        await login;
        expect(session.status.value).toBe('authenticated');
    });

    it('no comparte identidad entre instancias de aplicación', async () => {
        const first = createSession(service());
        const second = createSession(service());
        await first.restore();
        expect(second.user.value).toBeNull();
        expect(second.status.value).toBe('loading');
    });
});
