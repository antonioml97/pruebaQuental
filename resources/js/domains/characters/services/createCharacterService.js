import { ApiError } from '../../../shared/services/http/ApiError';
import { readCharacterQuery } from './characterQuery';

function validPage(payload) {
    const meta = payload?.meta;
    const links = payload?.links;
    return Array.isArray(payload?.data)
        && payload.data.every((item) => Number.isSafeInteger(item?.id) && item.id > 0
            && ['name', 'status', 'species', 'type', 'gender', 'image_url'].every((field) => typeof item[field] === 'string'))
        && ['current_page', 'last_page', 'per_page'].every((field) => Number.isSafeInteger(meta?.[field]) && meta[field] > 0)
        && Number.isSafeInteger(meta?.total) && meta.total >= 0
        && ['first', 'last'].every((field) => typeof links?.[field] === 'string')
        && ['prev', 'next'].every((field) => links?.[field] === null || typeof links?.[field] === 'string');
}

/** Consulta siempre el endpoint configurado, nunca sigue URLs recibidas en los enlaces. */
export function createCharacterService(client) {
    return {
        async list(criteria, { signal } = {}) {
            const params = Object.fromEntries(Object.entries(readCharacterQuery(criteria)).filter(([, value]) => value !== ''));
            const { data } = await client.get('/characters', { params, signal });
            if (!validPage(data)) {
                throw new ApiError({ code: 'invalid_response', message: 'El servidor no ha devuelto un catálogo válido.' });
            }
            return data;
        },
    };
}
