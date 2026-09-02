import { ApiError } from '../../../shared/services/http/ApiError';

/** Adapta el contrato HTTP a identidad pública; nunca devuelve el cuerpo completo. */
function publicUser(user) {
    if (!Number.isSafeInteger(user?.id) || user.id <= 0 || typeof user.name !== 'string' || typeof user.email !== 'string') {
        throw new ApiError({ code: 'invalid_response', message: 'El servidor no ha devuelto un usuario válido.' });
    }

    return { id: user.id, name: user.name, email: user.email };
}

export function createAuthenticationService(client) {
    function prepareCsrf({ signal } = {}) {
        return client.get('/auth/csrf-cookie', { signal }).then(() => undefined);
    }

    async function authenticate(path, credentials, { signal } = {}) {
        await prepareCsrf({ signal });
        const response = await client.post(path, credentials, { signal });
        return publicUser(response.data?.data?.user);
    }

    return {
        prepareCsrf,
        register({ name, email, password, password_confirmation }, options) {
            return authenticate('/auth/register', { name, email, password, password_confirmation }, options);
        },
        login({ email, password }, options) {
            return authenticate('/auth/login', { email, password }, options);
        },
        async currentUser({ signal } = {}) {
            const response = await client.get('/auth/user', { signal });
            return publicUser(response.data?.data);
        },
        async logout({ signal } = {}) {
            await prepareCsrf({ signal });
            await client.post('/auth/logout', undefined, { signal });
        },
    };
}
