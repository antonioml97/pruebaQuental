import { ApiError } from '../../../shared/services/http/ApiError';
import { readCharacterQuery } from './characterQuery';

export function validSummary(item) {
    return Number.isSafeInteger(item?.id) && item.id > 0
        && ['name', 'status', 'species', 'type', 'gender', 'image_url'].every((field) => typeof item[field] === 'string');
}

function validLocation(location) {
    return location === null || (Number.isSafeInteger(location?.id) && location.id > 0
        && ['name', 'type', 'dimension'].every((field) => typeof location[field] === 'string'));
}

function validDetail(item, externalId) {
    return validSummary(item) && item.id === externalId
        && validLocation(item.origin) && validLocation(item.current_location)
        && Array.isArray(item.episodes) && item.episodes.every((episode) => Number.isSafeInteger(episode?.id) && episode.id > 0
            && ['name', 'code', 'air_date'].every((field) => typeof episode[field] === 'string'));
}

export function validPage(payload) {
    const meta = payload?.meta;
    const links = payload?.links;
    return Array.isArray(payload?.data)
        && payload.data.every(validSummary)
        && ['current_page', 'last_page', 'per_page'].every((field) => Number.isSafeInteger(meta?.[field]) && meta[field] > 0)
        && Number.isSafeInteger(meta?.total) && meta.total >= 0
        && ['first', 'last'].every((field) => typeof links?.[field] === 'string')
        && ['prev', 'next'].every((field) => links?.[field] === null || typeof links?.[field] === 'string');
}

/** Consulta siempre el endpoint configurado, nunca sigue URLs recibidas en los enlaces. */
export function createCharacterService(client) {
    return {
        async detail(externalId, { signal } = {}) {
            if (!['string', 'number'].includes(typeof externalId) || !/^[1-9]\d*$/.test(String(externalId)) || !Number.isSafeInteger(Number(externalId))) {
                throw new ApiError({ code: 'character_not_found', status: 404, message: 'El identificador del personaje no es válido.' });
            }
            const { data } = await client.get(`/characters/${externalId}`, { signal });
            if (!validDetail(data?.data, Number(externalId))) {
                throw new ApiError({ code: 'invalid_response', message: 'El servidor no ha devuelto una ficha válida.' });
            }
            return data.data;
        },
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
