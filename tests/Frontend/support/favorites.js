import { ref } from 'vue';
import { favoritesKey } from '../../../resources/js/domains/favorites';

/** Aísla favoritos en pruebas de otros dominios, sin red ni estado entre casos. */
export function favoriteTestProvider() {
    return { [favoritesKey]: {
        items: ref([]), loading: ref(false), loaded: ref(true), saving: ref(false),
        error: ref(null), notice: ref(''), authenticated: ref(false), sessionLoading: ref(false), disabled: ref(true),
        has: () => false, retry: async () => {}, add: async () => {}, remove: async () => {},
    } };
}
