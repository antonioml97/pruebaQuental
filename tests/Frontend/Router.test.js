import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createMemoryHistory } from 'vue-router';
import { createAppRouter } from '../../resources/js/router';

beforeEach(() => vi.stubGlobal('scrollTo', vi.fn()));
afterEach(() => vi.unstubAllGlobals());

describe('Contrato de rutas', () => {
    it.each([
        ['/characters', 'characters', 'public', 'Personajes'],
        ['/characters/123', 'character-detail', 'public', 'Detalle del personaje'],
        ['/login', 'login', 'guest', 'Iniciar sesión'],
        ['/register', 'register', 'guest', 'Crear cuenta'],
        ['/favorites', 'favorites', 'authenticated', 'Favoritos'],
        ['/desconocida', 'not-found', 'public', 'Página no encontrada'],
    ])('resuelve %s con nombre, acceso, título y carga diferida', (path, name, access, title) => {
        const router = createAppRouter(createMemoryHistory());
        const route = router.resolve(path);
        expect(route.name).toBe(name);
        expect(route.meta).toEqual({ access, title });
        expect(route.matched[0].components.default).toBeTypeOf('function');
    });

    it('redirige la raíz al catálogo conservando la consulta', async () => {
        const router = createAppRouter(createMemoryHistory());
        await router.push('/?name=Rick');
        expect(router.currentRoute.value.fullPath).toBe('/characters?name=Rick');
    });

    it.each(['/characters/0', '/characters/-1', '/characters/no-valido', '/characters/1/otro'])('muestra 404 para el detalle inválido %s', (path) => {
        const router = createAppRouter(createMemoryHistory());
        expect(router.resolve(path).name).toBe('not-found');
    });

    it('no aplica todavía guardas de sesión', async () => {
        const router = createAppRouter(createMemoryHistory());
        for (const path of ['/favorites', '/login', '/register']) {
            await router.push(path);
            expect(router.currentRoute.value.path).toBe(path);
        }
    });

    it('restaura el scroll del historial y sitúa las navegaciones nuevas al principio', () => {
        const router = createAppRouter(createMemoryHistory());
        const to = router.resolve('/characters');
        const from = router.resolve('/login');
        const saved = { left: 0, top: 240 };
        expect(router.options.scrollBehavior(to, from, saved)).toEqual(saved);
        expect(router.options.scrollBehavior(to, from, null)).toEqual({ top: 0 });
    });
});
