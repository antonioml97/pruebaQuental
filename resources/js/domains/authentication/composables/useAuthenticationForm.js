import { computed, onUnmounted, ref } from 'vue';
import { onBeforeRouteLeave } from 'vue-router';
import { useSession } from '../../../shared/composables/useSession';
import { isRequestCancelled } from '../../../shared/services/http/ApiError';
import { validateAuthenticationForm } from '../services/validateAuthenticationForm';

/** Coordina validación, envío y errores de las dos pantallas, sin guardar credenciales globales. */
export function useAuthenticationForm(kind, onSuccess) {
    const session = useSession();
    const registration = kind === 'register';
    const values = ref({ name: '', email: '', password: '', password_confirmation: '' });
    const errors = ref({});
    const message = ref('');
    const pending = ref(false);
    const csrfExpired = ref(false);
    const busy = computed(() => pending.value || session.status.value === 'loading');

    // No se cancela una escritura al navegar: abortar no desharía una cookie ya emitida.
    onBeforeRouteLeave(() => !pending.value);
    onUnmounted(() => {
        values.value.password = '';
        values.value.password_confirmation = '';
    });

    async function submit() {
        if (busy.value || session.isAuthenticated.value) return;
        errors.value = validateAuthenticationForm(values.value, registration);
        message.value = '';
        csrfExpired.value = false;
        if (Object.keys(errors.value).length) {
            message.value = 'Revisa los campos indicados.';
            return;
        }

        pending.value = true;
        let succeeded = false;
        try {
            await session[kind]({ ...values.value, name: values.value.name.trim(), email: values.value.email.trim() });
            values.value.password = '';
            values.value.password_confirmation = '';
            succeeded = true;
        } catch (error) {
            if (!isRequestCancelled(error)) {
                csrfExpired.value = error.status === 419;
                message.value = csrfExpired.value
                    ? 'La protección CSRF no es válida. Pulsa «Renovar CSRF y reintentar» para preparar una nueva petición.'
                    : error.message;
                errors.value = Object.fromEntries(Object.entries(error.details ?? {})
                    .filter(([, messages]) => Array.isArray(messages))
                    .map(([field, messages]) => [field, messages.filter((item) => typeof item === 'string')]));
            }
        } finally {
            pending.value = false;
        }
        if (succeeded) await onSuccess();
    }

    return { values, errors, message, pending, busy, csrfExpired, submit };
}
