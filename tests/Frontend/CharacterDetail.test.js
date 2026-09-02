import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory, RouterView } from 'vue-router';
import { createAppRouter } from '../../resources/js/router';
import { characterServiceKey } from '../../resources/js/domains/characters';
import CharacterEpisodes from '../../resources/js/domains/characters/components/CharacterEpisodes.vue';
import CharacterProfile from '../../resources/js/domains/characters/components/CharacterProfile.vue';
import { ApiError } from '../../resources/js/shared/services/http/ApiError';
import { characterDetail, characterPage } from './support/characters';
import { deferred } from './support/http';
import { favoriteTestProvider } from './support/favorites';

enableAutoUnmount(afterEach);
beforeEach(() => vi.stubGlobal('scrollTo', vi.fn()));
afterEach(() => { vi.unstubAllGlobals(); document.body.replaceChildren(); });

async function setup(path = '/characters/1', detail = vi.fn(async () => characterDetail())) {
    const router = createAppRouter(createMemoryHistory());
    await router.push(path);
    const list = vi.fn(async (criteria) => characterPage({ page: criteria.page, perPage: criteria.per_page, total: 60 }));
    const wrapper = mount(RouterView, { attachTo: document.body, global: { plugins: [router], provide: { ...favoriteTestProvider(), [characterServiceKey]: { detail, list } } } });
    return { wrapper, router, detail, list };
}

describe('Ficha pública del personaje', () => {
    it('muestra datos, origen y localización separados, y episodios con fecha en castellano', async () => {
        const { wrapper, detail } = await setup();
        await flushPromises();
        expect(detail).toHaveBeenCalledWith('1', { signal: expect.any(AbortSignal) });
        expect(wrapper.get('h1').text()).toBe('Rick Sanchez');
        expect(wrapper.get('img').attributes()).toMatchObject({ alt: 'Retrato de Rick Sanchez', loading: 'eager' });
        expect(wrapper.text()).toContain('Vivo');
        expect(wrapper.text()).toContain('Masculino');
        expect(wrapper.text()).toContain('No especificado');
        const sections = wrapper.findAll('section');
        expect(sections.find((section) => section.find('h2').text() === 'Origen').text()).toContain('Earth (C-137)');
        expect(sections.find((section) => section.find('h2').text() === 'Localización actual').text()).toContain('Citadel of Ricks');
        expect(wrapper.text()).toContain('Desconocida');
        expect(wrapper.get('time').attributes('datetime')).toBe('2013-12-02');
        expect(wrapper.get('time').text()).toBe('2 de diciembre de 2013');
        expect(wrapper.get('#character-episodes').text()).toContain('S01E01');
        expect(wrapper.get('#character-episodes').text()).toContain('Pilot');
        expect(wrapper.find('button').exists()).toBe(false);
    });

    it.each([null, { id: 1, name: 'unknown', type: '', dimension: 'unknown' }])('explica las relaciones desconocidas %j y episodios ausentes', async (location) => {
        const { wrapper } = await setup('/characters/1', async () => characterDetail({ origin: location, current_location: location, episodes: [] }));
        await flushPromises();
        expect(wrapper.text().match(/Desconocido/g)).toHaveLength(2);
        expect(wrapper.text()).toContain('No hay episodios disponibles');
    });

    it('presenta carga accesible hasta recibir la ficha', async () => {
        const pending = deferred();
        const { wrapper } = await setup('/characters/1', () => pending.promise);
        expect(wrapper.get('[aria-busy]').attributes('aria-busy')).toBe('true');
        expect(wrapper.get('[role=status]').text()).toContain('Cargando ficha');
        expect(wrapper.find('img').exists()).toBe(false);
        pending.resolve(characterDetail());
        await flushPromises();
        expect(wrapper.get('[aria-busy]').attributes('aria-busy')).toBe('false');
    });

    it('muestra un 404 propio con vuelta al catálogo y sin reintentos', async () => {
        const detail = vi.fn().mockRejectedValue(new ApiError({ status: 404 }));
        const { wrapper } = await setup('/characters/999', detail);
        await flushPromises();
        expect(wrapper.get('h1').text()).toBe('Personaje no encontrado');
        expect(wrapper.get('[role=alert]').text()).toContain('no existe');
        expect(wrapper.get('a').attributes('href')).toBe('/characters');
        expect(wrapper.find('button').exists()).toBe(false);
        expect(detail).toHaveBeenCalledTimes(1);
    });

    it('permite reintentar explícitamente un fallo sin conservar errores anteriores', async () => {
        const detail = vi.fn().mockRejectedValueOnce(new ApiError({ status: 500, message: 'Servicio no disponible.' })).mockResolvedValueOnce(characterDetail());
        const { wrapper } = await setup('/characters/1', detail);
        await flushPromises();
        expect(wrapper.get('[role=alert]').text()).toContain('Servicio no disponible.');
        expect(detail).toHaveBeenCalledTimes(1);
        await wrapper.get('button').trigger('click');
        await flushPromises();
        expect(detail).toHaveBeenCalledTimes(2);
        expect(wrapper.get('h1').text()).toBe('Rick Sanchez');
        expect(wrapper.find('[role=alert]').exists()).toBe(false);
    });

    it.each(['éxito', 'error'])('cancela y descarta el %s obsoleto al cambiar de identificador', async (outcome) => {
        const old = deferred();
        const current = deferred();
        const detail = vi.fn().mockImplementationOnce(() => old.promise).mockImplementationOnce(() => current.promise);
        const { wrapper, router } = await setup('/characters/1', detail);
        await router.push('/characters/2');
        await flushPromises();
        expect(detail.mock.calls[0][1].signal.aborted).toBe(true);
        current.resolve(characterDetail({ id: 2, name: 'Morty Smith' }));
        await flushPromises();
        if (outcome === 'éxito') old.resolve(characterDetail());
        else old.reject(new ApiError({ message: 'Error antiguo' }));
        await flushPromises();
        expect(wrapper.get('h1').text()).toBe('Morty Smith');
        expect(wrapper.find('[role=alert]').exists()).toBe(false);
    });

    it('cancela al desmontar sin dejar errores tardíos sin tratar', async () => {
        const pending = deferred();
        const detail = vi.fn(() => pending.promise);
        const { wrapper } = await setup('/characters/1', detail);
        wrapper.unmount();
        expect(detail.mock.calls[0][1].signal.aborted).toBe(true);
        pending.reject(new Error('Respuesta tardía'));
        await flushPromises();
    });

    it('conserva búsqueda, filtros y página al abrir y cerrar la ficha', async () => {
        const query = '?name=Rick&status=Alive&species=Human&gender=Male&page=2&per_page=7';
        const { wrapper, router, list } = await setup(`/characters${query}`);
        await flushPromises();
        await wrapper.get('article a').trigger('click');
        await vi.waitFor(() => expect(wrapper.get('h1').text()).toBe('Rick Sanchez'));
        expect(router.currentRoute.value.fullPath).toBe(`/characters/1${query}`);
        await wrapper.get('a').trigger('click');
        await vi.waitFor(() => expect(wrapper.find('[name=name]').exists()).toBe(true));
        expect(router.currentRoute.value.fullPath).toBe(`/characters${query}`);
        expect(wrapper.get('[name=name]').element.value).toBe('Rick');
        expect(list.mock.lastCall[0]).toMatchObject({ page: 2, per_page: 7 });
    });

    it('solo usa parámetros del catálogo para regresar, sin nueva consulta por cambios de query', async () => {
        const { wrapper, router, detail } = await setup('/characters/1?name=Rick&page=2&returnTo=https://otro.test&status=INVALID');
        await flushPromises();
        expect(wrapper.get('a').attributes('href')).toBe('/characters?name=Rick&page=2');
        await router.replace('/characters/1?name=Morty&page=3');
        await flushPromises();
        expect(wrapper.get('a').attributes('href')).toBe('/characters?name=Morty&page=3');
        expect(detail).toHaveBeenCalledTimes(1);
    });
});

describe('Presentación de la ficha', () => {
    it('presenta episodios en bloques de 20 y lleva el foco al primer nuevo elemento', async () => {
        const episodes = Array.from({ length: 45 }, (_, i) => ({ id: i + 1, name: `Episodio ${i + 1}`, code: `S01E${i + 1}`, air_date: '2013-12-02' }));
        const wrapper = mount(CharacterEpisodes, { props: { episodes }, attachTo: document.body });
        expect(wrapper.findAll('li')).toHaveLength(20);
        await wrapper.get('button').trigger('click');
        expect(wrapper.findAll('li')).toHaveLength(40);
        expect(document.activeElement).toBe(wrapper.findAll('li')[20].element);
        await wrapper.get('button').trigger('click');
        expect(wrapper.findAll('li')).toHaveLength(45);
        expect(wrapper.find('button').exists()).toBe(false);
        expect(document.activeElement).toBe(wrapper.findAll('li')[40].element);
        await wrapper.setProps({ episodes: [...episodes] });
        expect(wrapper.findAll('li')).toHaveLength(20);
    });

    it.each(['', 'unknown', '2025-02-31'])('no inventa una fecha cuando recibe %j', (air_date) => {
        const wrapper = mount(CharacterEpisodes, { props: { episodes: [{ id: 1, name: 'Pilot', code: 'S01E01', air_date }] } });
        expect(wrapper.text()).toContain('Fecha no disponible');
        expect(wrapper.find('time').exists()).toBe(false);
    });

    it('ofrece un slot de acciones sin implementar favoritos', () => {
        const wrapper = mount(CharacterProfile, { props: { character: characterDetail() }, slots: { actions: '<button>Acción externa</button>' } });
        expect(wrapper.get('button').text()).toBe('Acción externa');
    });
});
