import { computed, inject, readonly, ref, watch } from 'vue';
import { isRequestCancelled } from '../../../shared/services/http/ApiError';

export const favoritesKey = Symbol('favorites');

/** Colección privada por aplicación: se borra e invalida al cambiar la sesión. */
export function createFavorites(service, session) {
    const items = ref([]);
    const loading = ref(false);
    const loaded = ref(false);
    const saving = ref(false);
    const error = ref(null);
    const notice = ref('');
    let request = null;

    function clear() {
        request?.abort();
        request = null;
        items.value = [];
        loaded.value = false;
        loading.value = false;
        saving.value = false;
        error.value = null;
        notice.value = '';
    }

    function report(failure, action = null) {
        if (isRequestCancelled(failure)) return;
        if (failure.status === 401) {
            session.expire();
            return;
        }
        error.value = {
            status: failure.status,
            message: failure.status === 419
                ? 'La protección CSRF ha caducado. Puedes renovarla y reintentar la acción.'
                : failure.message,
            action,
        };
    }

    async function load() {
        if (!session.isAuthenticated.value || loading.value || saving.value) return;
        const controller = new AbortController();
        request = controller;
        loading.value = true;
        loaded.value = false;
        error.value = null;
        try {
            // La API no incluye is_favorite: reunimos las páginas una vez por sesión.
            const collection = new Map();
            let page = 1;
            let lastPage;
            do {
                const result = await service.list(page, { signal: controller.signal });
                if (request !== controller) return;
                for (const character of result.data) collection.set(character.id, character);
                lastPage = result.meta.last_page;
                page++;
            } while (page <= lastPage);
            items.value = [...collection.values()];
            loaded.value = true;
        } catch (failure) {
            if (request === controller) report(failure);
        } finally {
            if (request === controller) {
                loading.value = false;
                request = null;
            }
        }
    }

    async function add(character) {
        if (!session.isAuthenticated.value || !loaded.value || loading.value || saving.value) return;
        const controller = new AbortController();
        request = controller;
        saving.value = true;
        error.value = null;
        notice.value = '';
        try {
            const saved = await service.add(character.id, { signal: controller.signal });
            if (request !== controller) return;
            const others = items.value.filter((item) => item.id !== character.id);
            items.value = [saved, ...others];
            notice.value = `${character.name}: añadido a favoritos.`;
        } catch (failure) {
            if (request === controller) report(failure, { character, type: 'add' });
        } finally {
            if (request === controller) {
                saving.value = false;
                request = null;
            }
        }
    }

    async function remove(character) {
        if (!session.isAuthenticated.value || !loaded.value || loading.value || saving.value) return;
        const controller = new AbortController();
        request = controller;
        saving.value = true;
        error.value = null;
        notice.value = '';
        try {
            await service.remove(character.id, { signal: controller.signal });
            if (request !== controller) return;
            items.value = items.value.filter((item) => item.id !== character.id);
            notice.value = `${character.name}: eliminado de favoritos.`;
        } catch (failure) {
            if (request === controller) report(failure, { character, type: 'remove' });
        } finally {
            if (request === controller) {
                saving.value = false;
                request = null;
            }
        }
    }

    function retry() {
        const action = error.value?.action;
        if (!action) return load();
        if (action.type === 'add') return add(action.character);
        return remove(action.character);
    }

    const stop = watch(() => session.isAuthenticated.value ? session.user.value?.id : null, (userId) => {
        clear();
        if (userId) void load();
    }, { immediate: true, flush: 'sync' });

    return {
        items: readonly(items), loading: readonly(loading), loaded: readonly(loaded),
        saving: readonly(saving), error: readonly(error), notice: readonly(notice),
        authenticated: session.isAuthenticated,
        sessionLoading: computed(() => session.status.value === 'loading'),
        disabled: computed(() => !session.isAuthenticated.value || !loaded.value || loading.value || saving.value),
        has: (id) => items.value.some((item) => item.id === id),
        add, remove, retry,
        dispose() { stop(); clear(); },
    };
}

export function useFavorites() {
    const favorites = inject(favoritesKey);
    if (!favorites) throw new Error('La aplicación no ha proporcionado los favoritos.');
    return favorites;
}
