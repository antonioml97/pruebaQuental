import { isCharacterPage, isCharacterSummary } from '../../characters';
import { ApiError } from '../../../shared/services/http/ApiError';

/** Reutiliza el contrato de personajes y prepara CSRF antes de cada escritura. */
export function createFavoriteService(client) {
    function favoritePath(id) {
        if (!Number.isSafeInteger(id) || id < 1) {
            throw new ApiError({ code: 'invalid_character', message: 'El identificador del personaje no es válido.' });
        }
        return `/favorites/${id}`;
    }

    return {
        async list(page, { signal } = {}) {
            const { data } = await client.get('/favorites', { params: { page, per_page: 100 }, signal });
            if (!isCharacterPage(data) || data.meta.current_page !== page || data.meta.per_page !== 100) {
                throw new ApiError({ code: 'invalid_response', message: 'El servidor no ha devuelto una página válida de favoritos.' });
            }
            return data;
        },
        async add(id, { signal } = {}) {
            const path = favoritePath(id);
            await client.get('/auth/csrf-cookie', { signal });
            const { data } = await client.put(path, undefined, { signal });
            if (!isCharacterSummary(data?.data) || data.data.id !== id) {
                throw new ApiError({ code: 'invalid_response', message: 'No se ha podido confirmar el favorito.' });
            }
            return data.data;
        },
        async remove(id, { signal } = {}) {
            const path = favoritePath(id);
            await client.get('/auth/csrf-cookie', { signal });
            await client.delete(path, { signal });
        },
    };
}
