import { computed, inject, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { characterServiceKey } from '../index';
import { readCharacterQuery, sameCharacterQuery, writeCharacterQuery } from '../services/characterQuery';
import { isRequestCancelled } from '../../../shared/services/http/ApiError';

/** La ruta es la fuente de búsqueda; cada consulta invalida y cancela la anterior. */
export function useCharacterCatalog() {
    const service = inject(characterServiceKey);
    if (!service) throw new Error('La aplicación no ha proporcionado el servicio de personajes.');
    const route = useRoute();
    const router = useRouter();
    const criteria = computed(() => readCharacterQuery(route.query));
    const result = ref(null);
    const loading = ref(true);
    const error = ref(null);
    const attempt = ref(0);

    watch([() => route.name, () => route.query, attempt], async ([name, query], previous, onCleanup) => {
        if (name !== 'characters') return;
        const normalized = readCharacterQuery(query);
        const canonical = writeCharacterQuery(normalized);
        result.value = null;
        error.value = null;
        loading.value = true;
        if (!sameCharacterQuery(query, canonical)) {
            await router.replace({ name: 'characters', query: canonical, hash: route.hash });
            return;
        }

        const controller = new AbortController();
        let active = true;
        onCleanup(() => { active = false; controller.abort(); });
        try {
            const page = await service.list(normalized, { signal: controller.signal });
            if (active) result.value = page;
        } catch (failure) {
            if (active && !isRequestCancelled(failure)) {
                error.value = { message: failure.message, details: failure.details ?? {} };
            }
        } finally {
            if (active) loading.value = false;
        }
    }, { immediate: true });

    function applyFilters(filters) {
        const next = readCharacterQuery({ ...filters, page: 1, per_page: criteria.value.per_page });
        return router.push({ name: 'characters', query: writeCharacterQuery(next) });
    }

    function changePage(page) {
        if (loading.value || !result.value || !Number.isSafeInteger(page) || page < 1 || page > result.value.meta.last_page) return;
        return router.push({ name: 'characters', query: writeCharacterQuery({ ...criteria.value, page }) });
    }

    function retry() {
        if (!loading.value) attempt.value++;
    }

    return { criteria, result, loading, error, applyFilters, changePage, retry };
}
