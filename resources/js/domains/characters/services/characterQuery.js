import { removeEmptyParams } from '../../../shared/utils/queryParams';

export const statusOptions = [
    { value: 'Alive', label: 'Vivo' }, { value: 'Dead', label: 'Muerto' }, { value: 'unknown', label: 'Desconocido' },
];
export const genderOptions = [
    { value: 'Female', label: 'Femenino' }, { value: 'Male', label: 'Masculino' },
    { value: 'Genderless', label: 'Sin género' }, { value: 'unknown', label: 'Desconocido' },
];

function text(value) {
    return typeof value === 'string' ? value.trim().slice(0, 255) : '';
}

function positiveInteger(value, fallback, max = Number.MAX_SAFE_INTEGER) {
    const parsed = typeof value === 'string' && /^\d+$/.test(value) ? Number(value) : value;
    return Number.isSafeInteger(parsed) && parsed > 0 && parsed <= max ? parsed : fallback;
}

/** Lista permitida del contrato: no se envían arrays, filtros ajenos ni enums inválidos. */
export function readCharacterQuery(query) {
    return {
        name: text(query.name),
        status: statusOptions.some((option) => option.value === query.status) ? query.status : '',
        species: text(query.species),
        gender: genderOptions.some((option) => option.value === query.gender) ? query.gender : '',
        page: positiveInteger(query.page, 1),
        per_page: positiveInteger(query.per_page, 20, 100),
    };
}

/** La URL omite valores vacíos y predeterminados, pero conserva búsquedas y tamaño de página. */
export function writeCharacterQuery(criteria) {
    return removeEmptyParams({
        name: criteria.name,
        status: criteria.status,
        species: criteria.species,
        gender: criteria.gender,
        page: criteria.page === 1 ? undefined : String(criteria.page),
        per_page: criteria.per_page === 20 ? undefined : String(criteria.per_page),
    });
}

export function sameCharacterQuery(left, right) {
    return Object.keys(left).length === Object.keys(right).length
        && Object.keys(left).every((key) => left[key] === right[key]);
}
