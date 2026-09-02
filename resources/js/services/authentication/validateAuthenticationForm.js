/** Validación básica de experiencia de usuario; las reglas completas siguen en Laravel. */
export function validateAuthenticationForm(values, registration = false) {
    const errors = {};
    if (registration && !values.name.trim()) errors.name = ['Indica tu nombre.'];
    if (!values.email.trim()) {
        errors.email = ['Indica tu correo electrónico.'];
    } else if (!/^[^\s@]+@[^\s@]+$/.test(values.email.trim())) {
        errors.email = ['Introduce un correo electrónico válido.'];
    }
    if (!values.password) {
        errors.password = ['Introduce tu contraseña.'];
    } else if (registration && values.password.length < 8) {
        errors.password = ['La contraseña debe tener al menos 8 caracteres.'];
    }
    if (registration && values.password_confirmation !== values.password) {
        errors.password_confirmation = ['Las contraseñas no coinciden.'];
    } else if (registration && !values.password_confirmation) {
        errors.password_confirmation = ['Repite tu contraseña.'];
    }
    return errors;
}
