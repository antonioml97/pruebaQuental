export const character = {
    id: 1, name: 'Rick Sanchez', status: 'Alive', species: 'Human', type: '', gender: 'Male',
    image_url: 'https://rickandmortyapi.com/api/character/avatar/1.jpeg',
};

export function characterPage({ data = [character], page = 1, perPage = 20, total = data.length } = {}) {
    const last = Math.max(1, Math.ceil(total / perPage));
    const url = (number) => `http://localhost/api/characters?page=${number}&per_page=${perPage}`;
    return {
        data,
        links: { first: url(1), last: url(last), prev: page > 1 ? url(page - 1) : null, next: page < last ? url(page + 1) : null },
        meta: { current_page: page, last_page: last, per_page: perPage, total },
    };
}

export function characterDetail(overrides = {}) {
    return {
        ...character,
        origin: { id: 1, name: 'Earth (C-137)', type: 'Planet', dimension: 'Dimension C-137' },
        current_location: { id: 3, name: 'Citadel of Ricks', type: 'Space station', dimension: 'unknown' },
        episodes: [{ id: 1, name: 'Pilot', code: 'S01E01', air_date: '2013-12-02' }],
        ...overrides,
    };
}

// Los tests de layout y autenticación aíslan los personajes sin peticiones de red.
export const emptyCharacterService = {
    list: async () => characterPage({ data: [] }),
    detail: async (id) => characterDetail({ id: Number(id), name: `Personaje #${id}` }),
};
