/** Espera a la sesión sin repetir consultas ni confundir loading con invitado. */
export function installSessionGuards(router, session) {
    return router.beforeEach(async (to) => {
        if (to.meta.access === 'public') return true;

        await session.whenIdle();
        if (to.meta.access === 'authenticated' && !session.isAuthenticated.value) {
            return { name: 'login', query: { redirect: to.fullPath }, replace: true };
        }
        if (to.meta.access === 'guest' && session.isAuthenticated.value) {
            return { name: 'characters', replace: true };
        }
        return true;
    });
}

/** Solo permite destinos conocidos de la SPA; nunca URLs externas ni formularios de acceso. */
export function loginDestination(value, router) {
    if (typeof value !== 'string' || !value.startsWith('/') || value.startsWith('//') || /[\\\u0000-\u0020]/.test(value)) {
        return { name: 'characters' };
    }
    const resolved = router.resolve(value);
    if (!resolved.name || resolved.name === 'not-found' || resolved.meta.access === 'guest') {
        return { name: 'characters' };
    }
    return resolved.fullPath;
}
