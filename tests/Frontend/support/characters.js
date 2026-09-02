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

// Los tests de layout y autenticación aíslan el catálogo sin iniciar peticiones de red.
export const emptyCharacterService = { list: async () => characterPage({ data: [] }) };
