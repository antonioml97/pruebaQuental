/** Elimina valores vacíos sin modificar el objeto ni descartar valores como 0 o false. */
export function removeEmptyParams(params) {
    return Object.fromEntries(
        Object.entries(params).filter(
            ([, value]) =>
                value !== '' &&
                value !== null &&
                value !== undefined
        )
    );
}
