import { afterEach, describe, expect, it, vi } from 'vitest';
import { AxiosError, CanceledError } from 'axios';
import { createApiClient } from '../../resources/js/shared/services/http/createApiClient';
import { ApiError, isRequestCancelled } from '../../resources/js/shared/services/http/ApiError';
import { rejectResponse, response } from './support/http';

afterEach(() => {
    vi.unstubAllEnvs();
    vi.unstubAllGlobals();
    document.cookie = 'XSRF-TOKEN=; Max-Age=0; path=/';
});

describe('Cliente HTTP', () => {
    it('usa la API relativa con cookies, CSRF y tiempo de espera limitado', async () => {
        vi.stubEnv('VITE_API_BASE_URL', '');
        const adapter = vi.fn(async (config) => response(config, { data: [] }));
        const client = createApiClient({ adapter });
        const result = await client.get('/characters');
        const config = adapter.mock.calls[0][0];

        expect(config).toMatchObject({ baseURL: '/api', withCredentials: true, withXSRFToken: true, timeout: 10000 });
        expect(config.headers.get('Accept')).toBe('application/json');
        expect(config.headers.has('Authorization')).toBe(false);
        expect(result.data).toEqual({ data: [] });
    });

    it('permite configurar la base por entorno o por instancia', () => {
        vi.stubEnv('VITE_API_BASE_URL', ' https://api.example.test/api ');
        expect(createApiClient().defaults.baseURL).toBe('https://api.example.test/api');
        expect(createApiClient({ baseURL: '/otra-api' }).defaults.baseURL).toBe('/otra-api');
    });

    it.each(['https://otro.example/api/auth/user', '//otro.example/api/auth/user', '/../../fuera-de-api'])('rechaza la URL %s antes de enviar cookies o CSRF', async (url) => {
        const adapter = vi.fn();
        const client = createApiClient({ adapter });
        await expect(client.get(url)).rejects.toMatchObject({ code: 'invalid_api_url' });
        expect(adapter).not.toHaveBeenCalled();
    });

    it('rechaza bases con credenciales incrustadas', () => {
        expect(() => createApiClient({ baseURL: 'https://usuario:secreto@example.test/api' })).toThrow(ApiError);
    });

    it.each([
        [401, 'unauthenticated', {}],
        [419, 'csrf_token_mismatch', {}],
        [422, 'validation_error', { email: ['El correo no es válido.'] }],
        [429, 'too_many_requests', {}],
    ])('conserva el contrato del error %i sin reintentos', async (status, code, details) => {
        const adapter = vi.fn((config) => rejectResponse(config, status, code, details, { 'retry-after': '30' }));
        const client = createApiClient({ adapter });
        const failure = await client.post('/auth/login', { password: 'dato-no-publico' }).catch((error) => error);

        expect(failure).toBeInstanceOf(ApiError);
        expect(failure).toMatchObject({ status, code, message: 'Mensaje del servidor.', details });
        expect(failure.data.error.details).toEqual(details);
        expect(failure.headers['retry-after']).toBe('30');
        expect(failure).not.toHaveProperty('config');
        expect(JSON.stringify(failure)).not.toContain('dato-no-publico');
        expect(adapter).toHaveBeenCalledTimes(1);
    });

    it.each([
        ['ERR_NETWORK', 'network_error'],
        ['ECONNABORTED', 'timeout'],
        ['ETIMEDOUT', 'timeout'],
    ])('normaliza %s en castellano', async (axiosCode, code) => {
        const client = createApiClient({ adapter: () => Promise.reject(new AxiosError('Network Error', axiosCode)) });
        await expect(client.get('/auth/user')).rejects.toMatchObject({ code, status: null });
    });

    it('conserva una respuesta inesperada sin tratarla como el contrato de validación', async () => {
        const client = createApiClient({ adapter: (config) => Promise.reject(new AxiosError('Server Error', 'ERR_BAD_RESPONSE', config, null, response(config, 'Servicio no disponible', 503))) });
        await expect(client.get('/auth/user')).rejects.toMatchObject({ status: 503, code: 'http_error', details: {}, data: 'Servicio no disponible' });
    });

    it('mantiene la cancelación identificable y respeta AbortSignal antes de enviar', async () => {
        const adapter = vi.fn(async (config) => response(config));
        const controller = new AbortController();
        controller.abort();
        const client = createApiClient({ adapter });
        const failure = await client.get('/auth/user', { signal: controller.signal }).catch((error) => error);
        expect(isRequestCancelled(failure)).toBe(true);
        expect(adapter).not.toHaveBeenCalled();

        const canceled = new CanceledError();
        const canceledClient = createApiClient({ adapter: () => Promise.reject(canceled) });
        await expect(canceledClient.get('/characters')).rejects.toBe(canceled);
    });

    it('el adaptador de navegador envía credenciales y copia únicamente la cookie CSRF a su cabecera', async () => {
        const requests = [];
        class FakeXHR {
            onloadend = null;
            status = 200;
            statusText = 'OK';
            responseText = '{}';
            headers = {};
            open(method, url) { this.method = method; this.url = url; }
            setRequestHeader(name, value) { this.headers[name] = value; }
            getAllResponseHeaders() { return 'content-type: application/json'; }
            send() { requests.push(this); queueMicrotask(() => this.onloadend()); }
        }
        vi.stubGlobal('XMLHttpRequest', FakeXHR);
        document.cookie = 'XSRF-TOKEN=prueba%2Bcsrf; path=/';
        const client = createApiClient({ adapter: 'xhr' });
        await client.post('/auth/logout');

        expect(requests[0].withCredentials).toBe(true);
        expect(requests[0].headers['X-XSRF-TOKEN']).toBe('prueba+csrf');
        expect(requests[0].headers).not.toHaveProperty('Authorization');
        expect(requests[0].url).toBe('/api/auth/logout');
    });
});
