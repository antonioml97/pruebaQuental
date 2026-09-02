/** Comprueba los campos compartidos por el catálogo y los favoritos. */
export function validSummary(item) {
    return Number.isSafeInteger(item?.id) && item.id > 0
        && ['name', 'status', 'species', 'type', 'gender', 'image_url'].every((field) => typeof item[field] === 'string');
}

function validLocation(location) {
    return location === null || (Number.isSafeInteger(location?.id) && location.id > 0
        && ['name', 'type', 'dimension'].every((field) => typeof location[field] === 'string'));
}

/** Comprueba las relaciones y que la ficha corresponda al identificador solicitado. */
export function validDetail(item, externalId) {
    return validSummary(item) && item.id === externalId
        && validLocation(item.origin) && validLocation(item.current_location)
        && Array.isArray(item.episodes) && item.episodes.every((episode) => Number.isSafeInteger(episode?.id) && episode.id > 0
            && ['name', 'code', 'air_date'].every((field) => typeof episode[field] === 'string'));
}

/** Valida los personajes y los metadatos del contrato de paginación. */
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
