import axios from 'axios';
import { ApiError, normalizeApiError } from './ApiError';

/** Cliente exclusivo de la API; las URLs de los servicios son relativas a esta base. */
export function createApiClient({ baseURL = import.meta.env.VITE_API_BASE_URL?.trim() || '/api', adapter } = {}) {
    const apiBase = new URL(baseURL, window.location.origin);
    const prefix = apiBase.pathname.replace(/\/$/, '');
    if (!['http:', 'https:'].includes(apiBase.protocol) || apiBase.username || apiBase.password || apiBase.search || apiBase.hash) {
        throw new ApiError({ code: 'invalid_api_url', message: 'La URL de la API debe ser HTTP(S), sin credenciales, consulta ni fragmento.' });
    }

    const client = axios.create({
        baseURL,
        timeout: 10000,
        withCredentials: true,
        withXSRFToken: true,
        xsrfCookieName: 'XSRF-TOKEN',
        xsrfHeaderName: 'X-XSRF-TOKEN',
        headers: { Accept: 'application/json' },
        ...(adapter ? { adapter } : {}),
    });

    // withXSRFToken también funciona entre orígenes: no debe usarse para URLs ajenas.
    client.interceptors.request.use((config) => {
        const destination = new URL(client.getUri(config), window.location.origin);
        if (destination.origin !== apiBase.origin || destination.username || destination.password
            || (destination.pathname !== prefix && !destination.pathname.startsWith(`${prefix}/`))) {
            throw new ApiError({ code: 'invalid_api_url', message: 'La petición debe dirigirse a la API configurada.' });
        }
        return config;
    });

    client.interceptors.response.use(
        (response) => response,
        (error) => Promise.reject(normalizeApiError(error)),
    );

    return client;
}
