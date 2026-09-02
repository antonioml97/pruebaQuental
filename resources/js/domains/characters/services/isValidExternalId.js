/** Acepta un entero positivo seguro o su representación decimal sin ceros iniciales. */
export function isValidExternalId(externalId) {
    return ['string', 'number'].includes(typeof externalId)
        && /^[1-9]\d*$/.test(String(externalId))
        && Number.isSafeInteger(Number(externalId));
}
