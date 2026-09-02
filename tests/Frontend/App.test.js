import { afterEach, describe, expect, it, vi } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import App from '../../resources/js/App.vue';

enableAutoUnmount(afterEach);

afterEach(() => {
    vi.unstubAllEnvs();
});

describe('Montaje inicial de la SPA', () => {
    it('presenta el contenido inicial sin advertencias ni errores de Vue', () => {
        const warn = vi.spyOn(console, 'warn');
        const error = vi.spyOn(console, 'error');
        const wrapper = mount(App);

        expect(wrapper.get('main h1').text()).toBe('El multiverso empieza aquí.');
        expect(wrapper.get('section').attributes('aria-labelledby')).toBe(wrapper.get('h1').attributes('id'));
        expect(wrapper.text()).toContain('Aplicación en desarrollo');
        expect(warn).not.toHaveBeenCalled();
        expect(error).not.toHaveBeenCalled();
    });

    it('enlaza a Swagger sin montar su interfaz dentro de Vue', () => {
        const wrapper = mount(App);

        expect(wrapper.get('a').attributes('href')).toBe('/docs');
        expect(wrapper.get('a').text()).toContain('Consultar la documentación de la API');
        expect(wrapper.find('#swagger-ui').exists()).toBe(false);
    });

    it('muestra el nombre público configurado', () => {
        vi.stubEnv('VITE_APP_NAME', 'Mi catálogo');

        expect(mount(App).get('header').text()).toContain('Mi catálogo');
    });

    it('permite arrancar sin un nombre público configurado', () => {
        vi.stubEnv('VITE_APP_NAME', '  ');

        expect(mount(App).get('header').text()).toContain('Rick and Morty');
    });
});
