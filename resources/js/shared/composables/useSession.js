import { computed, inject, readonly, ref } from 'vue';
import { ApiError, isRequestCancelled } from '../services/http/ApiError';

export const sessionKey = Symbol('session');

/** Estado por aplicación, sin almacenamiento persistente ni secretos del transporte. */
export function createSession(authentication) {
    const status = ref('loading');
    const user = ref(null);
    const error = ref(null);
    let pending = null;
    let pendingKind = null;

    function run(kind, operation, { anonymousIsExpected = false } = {}) {
        if (pending) {
            return Promise.reject(new ApiError({
                code: 'session_busy',
                message: 'Espera a que termine la operación de sesión en curso.',
            }));
        }

        const previousStatus = user.value ? 'authenticated' : 'guest';
        status.value = 'loading';
        error.value = null;
        pendingKind = kind;

        pending = Promise.resolve().then(operation).then((identity) => {
            user.value = identity ?? null;
            status.value = identity ? 'authenticated' : 'guest';
            return user.value ? readonly(user.value) : null;
        }).catch((failure) => {
            if (failure.status === 401) {
                user.value = null;
                status.value = 'guest';
                if (anonymousIsExpected) {
                    return null;
                }
            } else {
                // Cancelar o perder la red no demuestra que la sesión haya caducado.
                status.value = previousStatus;
            }

            if (!isRequestCancelled(failure)) {
                error.value = {
                    code: failure.code,
                    message: failure.message,
                    details: failure.details,
                    status: failure.status,
                };
            }
            throw failure;
        }).finally(() => {
            pending = null;
            pendingKind = null;
        });

        return pending;
    }

    return {
        status: readonly(status),
        user: readonly(user),
        error: readonly(error),
        isAuthenticated: computed(() => status.value === 'authenticated'),
        // Un 401 de una operación privada vigente confirma la pérdida de sesión.
        expire() {
            if (status.value !== 'authenticated') return;
            user.value = null;
            status.value = 'guest';
            error.value = { code: 'session_expired', status: 401, message: 'Tu sesión ha caducado. Inicia sesión de nuevo.' };
        },
        // Las guardas esperan la operación existente y consultan después el estado final.
        whenIdle() {
            return (pending ?? Promise.resolve()).catch(() => null);
        },
        restore(options) {
            if (pendingKind === 'restore') {
                return pending;
            }
            return run('restore', () => authentication.currentUser(options), { anonymousIsExpected: true });
        },
        login(credentials, options) {
            return run('login', () => authentication.login(credentials, options));
        },
        register(credentials, options) {
            return run('register', () => authentication.register(credentials, options));
        },
        logout(options) {
            return run('logout', () => authentication.logout(options), { anonymousIsExpected: true });
        },
    };
}

/** Accede a la sesión proporcionada por el arranque, compartida entre las vistas. */
export function useSession() {
    const session = inject(sessionKey);
    if (!session) {
        throw new Error('La aplicación no ha proporcionado el estado de sesión.');
    }
    return session;
}
