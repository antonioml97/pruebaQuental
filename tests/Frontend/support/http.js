import { AxiosError } from 'axios';

/** Respuesta de un adaptador Axios simulado, sin peticiones de red. */
export function response(config, data = {}, status = 200, headers = {}) {
    return { config, data, status, headers, statusText: String(status) };
}

export function rejectResponse(config, status, code, details = {}, headers = {}) {
    const result = response(config, { error: { code, message: 'Mensaje del servidor.', details } }, status, headers);
    return Promise.reject(new AxiosError('Request failed', 'ERR_BAD_RESPONSE', config, null, result));
}

export function deferred() {
    let resolve;
    let reject;
    const promise = new Promise((resolvePromise, rejectPromise) => {
        resolve = resolvePromise;
        reject = rejectPromise;
    });
    return { promise, resolve, reject };
}
