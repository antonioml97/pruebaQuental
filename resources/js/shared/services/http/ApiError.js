import axios from 'axios';

/** Error público uniforme; no conserva la configuración con credenciales de la petición. */
export class ApiError extends Error {
    constructor({ code, message, status = null, details = {}, data = null, headers = {} }) {
        super(message);
        this.name = 'ApiError';
        this.code = code;
        this.status = status;
        this.details = details;
        this.data = data;
        this.headers = headers;
    }
}

export const isRequestCancelled = (error) => axios.isCancel(error);

/** Conserva el contrato y las cabeceras útiles, distinguiendo cancelación, red y HTTP. */
export function normalizeApiError(error) {
    if (error instanceof ApiError || isRequestCancelled(error)) {
        return error;
    }

    const response = error.response;
    const payload = response?.data?.error;
    const timedOut = ['ECONNABORTED', 'ETIMEDOUT'].includes(error.code);
    let code = 'network_error';
    let message = 'No se ha podido conectar con el servidor.';

    if (response) {
        code = 'http_error';
        message = 'No se ha podido completar la petición.';
    } else if (timedOut) {
        code = 'timeout';
        message = 'La petición ha superado el tiempo de espera.';
    }

    return new ApiError({
        code: typeof payload?.code === 'string' ? payload.code : code,
        message: typeof payload?.message === 'string' ? payload.message : message,
        status: response?.status ?? null,
        details: payload?.details && typeof payload.details === 'object' ? payload.details : {},
        data: response?.data ?? null,
        headers: response?.headers ?? {},
    });
}
