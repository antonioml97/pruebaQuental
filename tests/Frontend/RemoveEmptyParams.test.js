import { describe, expect, it } from 'vitest';
import { removeEmptyParams } from '../../resources/js/shared/utils/queryParams';

describe('Limpieza de parámetros vacíos', () => {
    it('elimina únicamente cadenas vacías, null y undefined', () => {
        expect(removeEmptyParams({ name: '', status: null, species: undefined, page: 2, gender: 'Male' }))
            .toEqual({ page: 2, gender: 'Male' });
    });

    it('conserva cero, false, espacios y colecciones vacías', () => {
        const params = { zero: 0, enabled: false, spaces: ' ', list: [], object: {} };
        expect(removeEmptyParams(params)).toEqual(params);
    });

    it('devuelve otro objeto sin modificar el original ni limpiar valores anidados', () => {
        const nested = { name: '', optional: null };
        const params = Object.freeze({ empty: '', nested });
        const result = removeEmptyParams(params);
        expect(result).not.toBe(params);
        expect(params.empty).toBe('');
        expect(result).toEqual({ nested });
        expect(result.nested).toBe(nested);
    });

    it('admite un objeto vacío', () => {
        expect(removeEmptyParams({})).toEqual({});
    });
});
