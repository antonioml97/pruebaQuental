import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory } from 'vue-router';
import { createAppRouter } from '../../resources/js/router';
import { characterServiceKey } from '../../resources/js/domains/characters';
import CharactersView from '../../resources/js/domains/characters/views/CharactersView.vue';
import CharacterCard from '../../resources/js/domains/characters/components/CharacterCard.vue';
import { ApiError } from '../../resources/js/shared/services/http/ApiError';
import { character, characterPage } from './support/characters';
import { deferred } from './support/http';
import { favoriteTestProvider } from './support/favorites';

enableAutoUnmount(afterEach);
beforeEach(() => vi.stubGlobal('scrollTo', vi.fn()));
afterEach(() => { vi.unstubAllGlobals(); document.body.replaceChildren(); });

async function setup(path = '/characters', list = vi.fn(async (criteria) => characterPage({ page: criteria.page, perPage: criteria.per_page, total: 60 }))) {
    const router = createAppRouter(createMemoryHistory());
    await router.push(path);
    const wrapper = mount(CharactersView, { attachTo: document.body, global: { plugins: [router], provide: { ...favoriteTestProvider(), [characterServiceKey]: { list } } } });
    return { wrapper, router, list };
}

describe('Catálogo de personajes', () => {
    it('recupera filtros y página desde la URL y muestra todos los atributos', async () => {
        const { wrapper, list } = await setup('/characters?name=Rick&status=Alive&species=Human&gender=Male&page=2&per_page=7');
        await flushPromises();
        expect(list.mock.calls[0][0]).toEqual({ name: 'Rick', status: 'Alive', species: 'Human', gender: 'Male', page: 2, per_page: 7 });
        expect(wrapper.get('[name=name]').element.value).toBe('Rick');
        expect(wrapper.get('[name=status]').element.value).toBe('Alive');
        expect(wrapper.get('article h2').text()).toBe('Rick Sanchez');
        expect(wrapper.get('article').text()).toContain('No especificado');
        expect(wrapper.get('article').text()).toContain('Masculino');
        expect(wrapper.get('article').text()).toContain('Vivo');
        expect(wrapper.get('article img').attributes('alt')).toBe('Retrato de Rick Sanchez');
        expect(wrapper.get('article img').attributes('loading')).toBe('lazy');
        expect(wrapper.get('article a').attributes('href')).toBe('/characters/1?name=Rick&status=Alive&species=Human&gender=Male&page=2&per_page=7');
        expect(wrapper.get('[role=status]').text()).toContain('60 personajes');
    });

    it('aplica los filtros juntos, reinicia página y conserva el tamaño', async () => {
        const { wrapper, router, list } = await setup('/characters?page=3&per_page=7');
        await flushPromises();
        await wrapper.get('[name=name]').setValue(' Morty ');
        await wrapper.get('[name=species]').setValue('Alien');
        await wrapper.get('[name=status]').setValue('Dead');
        await wrapper.get('[name=gender]').setValue('Female');
        expect(list).toHaveBeenCalledTimes(1);
        await wrapper.get('form').trigger('submit');
        await vi.waitFor(() => expect(list).toHaveBeenCalledTimes(2));
        expect(router.currentRoute.value.query).toEqual({ name: 'Morty', species: 'Alien', status: 'Dead', gender: 'Female', per_page: '7' });
        expect(list.mock.calls[1][0].page).toBe(1);
    });

    it('limpia filtros y permite restaurar búsqueda y página con atrás y adelante', async () => {
        const { wrapper, router, list } = await setup('/characters?name=Rick&page=2');
        await flushPromises();
        await wrapper.get('form button[type=button]').trigger('click');
        await vi.waitFor(() => expect(router.currentRoute.value.fullPath).toBe('/characters'));
        router.back();
        await vi.waitFor(() => expect(wrapper.get('[name=name]').element.value).toBe('Rick'));
        expect(list.mock.lastCall[0]).toMatchObject({ name: 'Rick', page: 2 });
        router.forward();
        await vi.waitFor(() => expect(wrapper.get('[name=name]').element.value).toBe(''));
        expect(list.mock.lastCall[0]).toMatchObject({ name: '', page: 1 });
    });

    it('normaliza la URL antes de consultar y no envía arrays ni claves desconocidas', async () => {
        const { router, list } = await setup('/characters?name=Rick&name=Morty&status=alive&page=-1&per_page=101&extra=1');
        await vi.waitFor(() => expect(list).toHaveBeenCalledTimes(1));
        expect(router.currentRoute.value.fullPath).toBe('/characters');
        expect(list.mock.calls[0][0]).toEqual({ name: '', species: '', status: '', gender: '', page: 1, per_page: 20 });
    });

    it('usa links y meta para paginar sin navegar a URLs del servidor', async () => {
        const list = vi.fn(async (criteria) => {
            const page = characterPage({ page: criteria.page, total: 60 });
            page.links.next = 'https://otro.test/api/characters?page=2';
            return page;
        });
        const { wrapper, router } = await setup('/characters?name=Rick', list);
        await flushPromises();
        expect(wrapper.get('[aria-label="Anterior página"]').attributes('disabled')).toBeDefined();
        await wrapper.get('[aria-label="Siguiente página"]').trigger('click');
        await vi.waitFor(() => expect(list).toHaveBeenCalledTimes(2));
        expect(router.currentRoute.value.query).toEqual({ name: 'Rick', page: '2' });
        await wrapper.get('[aria-label="Última página"]').trigger('click');
        await vi.waitFor(() => expect(list).toHaveBeenCalledTimes(3));
        expect(router.currentRoute.value.query.page).toBe('3');
        expect(wrapper.get('[aria-label="Siguiente página"]').attributes('disabled')).toBeDefined();
    });

    it('respeta un enlace siguiente nulo aunque los metadatos indiquen más páginas', async () => {
        const { wrapper } = await setup('/characters', async () => ({ ...characterPage({ total: 60 }), links: { ...characterPage().links, next: null } }));
        await flushPromises();
        expect(wrapper.get('[aria-label="Siguiente página"]').attributes('disabled')).toBeDefined();
    });

    it('muestra carga accesible y oculta los datos de una búsqueda anterior', async () => {
        const pending = deferred();
        const list = vi.fn().mockResolvedValueOnce(characterPage()).mockImplementationOnce(() => pending.promise);
        const { wrapper, router } = await setup('/characters', list);
        await flushPromises();
        expect(wrapper.find('article').exists()).toBe(true);
        await router.push('/characters?name=Morty');
        await flushPromises();
        expect(wrapper.get('#character-results').attributes('aria-busy')).toBe('true');
        expect(wrapper.get('[role=status]').text()).toBe('Cargando personajes…');
        expect(wrapper.find('article').exists()).toBe(false);
        pending.resolve(characterPage({ data: [] }));
        await flushPromises();
        expect(wrapper.get('#character-results').attributes('aria-busy')).toBe('false');
    });

    it.each(['success', 'failure'])('descarta el %s obsoleto aunque el transporte ignore la cancelación', async (outcome) => {
        const old = deferred();
        const current = deferred();
        const list = vi.fn().mockImplementationOnce(() => old.promise).mockImplementationOnce(() => current.promise);
        const { wrapper, router } = await setup('/characters?name=Rick', list);
        await router.push('/characters?name=Morty');
        await flushPromises();
        expect(list.mock.calls[0][1].signal.aborted).toBe(true);
        current.resolve(characterPage({ data: [{ ...character, id: 2, name: 'Morty Smith' }] }));
        await flushPromises();
        if (outcome === 'success') old.resolve(characterPage());
        else old.reject(new ApiError({ message: 'Error antiguo' }));
        await flushPromises();
        expect(wrapper.get('article h2').text()).toBe('Morty Smith');
        expect(wrapper.find('[role=alert]').exists()).toBe(false);
    });

    it('cancela al desmontar y no deja errores tardíos sin tratar', async () => {
        const pending = deferred();
        const list = vi.fn(() => pending.promise);
        const { wrapper } = await setup('/characters', list);
        wrapper.unmount();
        expect(list.mock.calls[0][1].signal.aborted).toBe(true);
        pending.reject(new Error('Respuesta tardía'));
        await flushPromises();
    });

    it('una respuesta obsoleta no apaga la carga de la petición vigente', async () => {
        const old = deferred();
        const current = deferred();
        const list = vi.fn().mockImplementationOnce(() => old.promise).mockImplementationOnce(() => current.promise);
        const { wrapper, router } = await setup('/characters', list);
        await router.push('/characters?name=Morty');
        old.resolve(characterPage());
        await flushPromises();
        expect(wrapper.get('#character-results').attributes('aria-busy')).toBe('true');
        expect(wrapper.find('article').exists()).toBe(false);
        current.resolve(characterPage({ data: [] }));
        await flushPromises();
        expect(wrapper.get('#character-results').attributes('aria-busy')).toBe('false');
    });

    it('distingue el catálogo vacío de una página fuera de rango y ofrece volver', async () => {
        const { wrapper, router } = await setup('/characters?page=9', async (criteria) => characterPage({ data: [], page: criteria.page }));
        await flushPromises();
        expect(wrapper.text()).toContain('Esta página no tiene resultados');
        await wrapper.get('#character-results > div button').trigger('click');
        await vi.waitFor(() => expect(router.currentRoute.value.query.page).toBeUndefined());
        expect(wrapper.text()).toContain('No hay personajes para esta búsqueda');
        expect(wrapper.find('article').exists()).toBe(false);
    });

    it.each([422, 429, 500, null])('muestra error %s y permite reintentar una sola vez por acción', async (status) => {
        const list = vi.fn().mockRejectedValueOnce(new ApiError({ status, message: 'Fallo de prueba', details: { name: ['Revisa el nombre.'] } })).mockResolvedValue(characterPage());
        const { wrapper } = await setup('/characters', list);
        await flushPromises();
        expect(wrapper.get('[role=alert]').text()).toContain('Fallo de prueba');
        expect(wrapper.get('[role=alert]').text()).toContain('Revisa el nombre.');
        expect(list).toHaveBeenCalledTimes(1);
        await wrapper.get('[role=alert] button').trigger('click');
        await flushPromises();
        expect(list).toHaveBeenCalledTimes(2);
        expect(wrapper.find('[role=alert]').exists()).toBe(false);
        expect(wrapper.find('article').exists()).toBe(true);
    });

    it('ofrece una alternativa accesible si falla la imagen', async () => {
        const { wrapper } = await setup();
        await flushPromises();
        await wrapper.get('img').trigger('error');
        expect(wrapper.find('img').exists()).toBe(false);
        expect(wrapper.get('[role=img]').attributes('aria-label')).toContain('Rick Sanchez');
    });

    it('no incorpora URLs de imagen ejecutables', async () => {
        const router = createAppRouter(createMemoryHistory());
        const wrapper = mount(CharacterCard, { props: { character: { ...character, image_url: 'javascript:alert(1)' } }, global: { plugins: [router] } });
        expect(wrapper.find('img').exists()).toBe(false);
    });
});
