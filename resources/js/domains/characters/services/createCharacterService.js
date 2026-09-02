import { ApiError } from '../../../shared/services/http/ApiError';
import { removeEmptyParams } from '../../../shared/utils/queryParams';
import { readCharacterQuery } from './characterQuery';
import { validDetail, validPage } from './characterResponseValidation';
import { isValidExternalId } from './isValidExternalId';

/** Consulta siempre el endpoint configurado, nunca sigue URLs recibidas en los enlaces. */
export function createCharacterService(client) {
    return {
        async detail(externalId, { signal } = {}) {
            if (!isValidExternalId(externalId)) {
                throw new ApiError({ code: 'character_not_found', status: 404, message: 'El identificador del personaje no es válido.' });
            }
            const { data } = await client.get(`/characters/${externalId}`, { signal });
            if (!validDetail(data?.data, Number(externalId))) {
                throw new ApiError({ code: 'invalid_response', message: 'El servidor no ha devuelto una ficha válida.' });
            }
            return data.data;
        },
        async list(criteria, { signal } = {}) {
            const params = removeEmptyParams(readCharacterQuery(criteria));
            const { data } = await client.get('/characters', { params, signal });
            if (!validPage(data)) {
                throw new ApiError({ code: 'invalid_response', message: 'El servidor no ha devuelto un catálogo válido.' });
            }
            return data;
        },
    };
}
