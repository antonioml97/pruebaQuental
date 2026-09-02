import { inject, ref, watch } from 'vue';
import { characterServiceKey } from '../index';
import { isRequestCancelled } from '../../../shared/services/http/ApiError';

/** Carga la ficha por identificador público y descarta cualquier respuesta anterior. */
export function useCharacterDetail(externalId) {
    const service = inject(characterServiceKey);
    if (!service) throw new Error('La aplicación no ha proporcionado el servicio de personajes.');
    const character = ref(null);
    const loading = ref(true);
    const error = ref(null);
    const attempt = ref(0);
    watch([externalId, attempt], async ([id], previous, onCleanup) => {
        const controller = new AbortController();
        let active = true;
        onCleanup(() => { active = false; controller.abort(); });
        character.value = null;
        error.value = null;
        loading.value = true;
        try {
            const result = await service.detail(id, { signal: controller.signal });
            if (active) character.value = result;
        } catch (failure) {
            if (active && !isRequestCancelled(failure)) error.value = { status: failure.status, message: failure.message };
        } finally {
            if (active) loading.value = false;
        }
    }, { immediate: true });

    function retry() {
        if (!loading.value) attempt.value++;
    }
    return { character, loading, error, retry };
}
