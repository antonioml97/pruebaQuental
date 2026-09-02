# API de Rick and Morty

Backend REST desarrollado con Laravel 12 para sincronizar el catálogo público de
Rick and Morty, consultarlo con filtros y gestionar los personajes favoritos de
cada usuario. Incluye la base de una SPA Vue 3, compilada con Vite y estilizada con
Tailwind CSS. El entorno local se ejecuta con Laravel Sail, PHP y MySQL.

## Requisitos

- Docker Desktop o Docker Engine con Docker Compose.
- Git.
- Node.js 24 (24.15 o superior) y npm para Vue, Swagger UI y sus validaciones.
  También se pueden usar los incluidos en Sail mediante `./vendor/bin/sail npm`.
- WSL2 en Windows. Git, Sail, npm y los hooks del repositorio deben ejecutarse desde WSL2.

No es necesario instalar PHP ni MySQL directamente en el equipo si se utiliza
el contenedor de Composer indicado durante la instalación.

## Instalación

Desde una terminal WSL2:

```sh
git clone <url-del-repositorio>
cd pruebaQuental
cp .env.example .env

docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$PWD:/var/www/html" \
  -w /var/www/html \
  laravelsail/php84-composer:latest \
  composer install --ignore-platform-reqs

./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
npm ci
npm run build
```

`npm ci` configura también el hook `pre-push` de Husky. La aplicación queda
disponible en [http://localhost](http://localhost) y su estado se puede comprobar
en [http://localhost/up](http://localhost/up).

Para detener los contenedores:

```sh
./vendor/bin/sail down
```

## Desarrollo del frontend

La entrada `/` sirve una vista Blade mínima que monta `App.vue` y redirige al
catálogo provisional en `/characters`. Vue Router 4 utiliza historial HTML5 y
carga cada vista de forma diferida, dentro de un layout compartido con navegación
adaptable a móvil. Todavía no hay formularios, guardas de sesión ni consultas a
la API: se incorporarán en los issues #27–#31.

| URL | Vista | Acceso previsto |
| --- | --- | --- |
| `/characters` | Catálogo provisional. | Público. |
| `/characters/:externalId` | Detalle provisional, identificador entero positivo. | Público. |
| `/login` | Inicio de sesión provisional. | Invitado. |
| `/register` | Registro provisional. | Invitado. |
| `/favorites` | Favoritos provisionales, sin datos privados. | Autenticado. |
| Cualquier otra ruta del cliente | Página no encontrada. | Público. |

Los metadatos `title` y `access` centralizan el título y el acceso previsto de
cada ruta; **todavía no son un control de sesión**. La API mantiene su propia
autenticación y autorización. El layout actualiza el título del documento y lleva
el foco al contenido al navegar. Incluye un enlace «Saltar al contenido» y un
menú móvil con botón nativo, estado `aria-expanded` y cierre mediante Escape.

Laravel entrega el documento de Vue para las consultas GET/HEAD de rutas internas,
permitiendo abrir enlaces directamente y recargar. Excluye `/api`, `/docs`, `/up`
y sus descendientes, además de `/build` y `/storage`, para no convertir errores
de servicios o archivos en HTML de la SPA. Una ruta desconocida del cliente
recibe el documento HTTP 200 y muestra la página 404 de Vue; las rutas reservadas
inexistentes conservan su error HTTP (404, o el rechazo de firma del disco privado).

Con Sail en marcha, ejecuta en WSL2:

```sh
npm ci
npm run dev
```

Abre **http://localhost**, no el puerto 5173: Laravel entrega el HTML y Vite sirve
los recursos con recarga en caliente (HMR). Un cambio de plantilla en
`resources/js/views/CharactersView.vue` debe verse sin recargar manualmente.
Si no tienes Node en WSL2, usa los mismos scripts dentro de Sail:

```sh
./vendor/bin/sail npm ci
./vendor/bin/sail npm run dev -- --host 0.0.0.0
```

Vite anuncia HMR en `localhost` y usa polling dentro de Sail para detectar también
los cambios realizados desde Windows en carpetas compartidas. Escuchar en
`0.0.0.0` permite acceder al contenedor; no es la dirección que debe abrir el navegador.

Detén Vite con `Ctrl+C` y ejecuta `npm run build` (o `./vendor/bin/sail npm run build`)
para comprobar los recursos de producción. Vite optimiza y minifica en
`public/build`; no se añade otro bundler ni minificador. `public/hot` indica a
Laravel cuándo usar el servidor de desarrollo y se elimina al detenerlo
normalmente. Ambos son artefactos locales que no se versionan.

`resources/js/swagger.js` sigue siendo una entrada independiente, cargada solo en
`/docs`. La SPA no descarga Swagger ni sus estilos. El aviso de tamaño del bundle
de Swagger durante el build no corresponde al bundle de Vue.

### Organización del código cliente

| Ubicación bajo `resources/js` | Responsabilidad |
| --- | --- |
| `app.js` y `App.vue` | Montaje y componente raíz. |
| `components/` | Piezas de presentación reutilizables, como `BrandMark.vue`. |
| `views/` | Pantallas diferidas de catálogo, detalle, acceso, registro, favoritos y 404. |
| `layouts/` | `MainLayout.vue`: cabecera, navegación, contenido y pie compartidos. |
| `router/` | Mapa explícito de rutas, metadatos e historial con Vue Router. |
| `composables/` | Estado y lógica reactiva reutilizable (#27). |
| `services/` | Acceso HTTP y adaptación del contrato de la API (#27). |

Las carpetas previstas se crearán al incorporar su primer módulo; no se añaden
archivos vacíos ni implementaciones ficticias. Los componentes usan Composition
API con `<script setup>`. `resources/css/app.css` centraliza el tema de Tailwind:
colores, tipografía del sistema, foco visible y la escala de espaciado de Tailwind.
No se descargan fuentes externas.
Tailwind registra explícitamente las fuentes Blade, JavaScript y Vue; no escanea
todo el repositorio ni las vistas compiladas en `storage`.

### Variables públicas

- `VITE_APP_NAME`: nombre de la cabecera y sufijo del título. `.env.example` lo toma de
  `APP_NAME`; si queda vacío, se muestra «Rick and Morty».
- `APP_URL`: origen de Laravel (por defecto `http://localhost`), configuración del
  servidor, no una variable pública de JavaScript.
- `FRONTEND_URL`: origen permitido por CORS si se utiliza un cliente separado.
  El puerto de Vite sirve recursos; no cambia el origen de la SPA entregada por Laravel.

No se necesita una URL pública de API en esta entrega porque todavía no hay
peticiones desde Vue. Todo valor con prefijo `VITE_` queda expuesto en el cliente:
**nunca incluyas contraseñas, tokens ni claves privadas**. Reinicia Vite o vuelve a
compilar después de cambiar estas variables.

### Pruebas de componentes

```sh
npm test
npm run test:watch
```

Vitest usa el plugin de Vue y jsdom con Vue Test Utils. Las pruebas se encuentran
en `tests/Frontend`. Su configuración no carga el plugin Laravel, evitando que
las pruebas unitarias dependan de PHP, del servidor Vite o de `public/hot`.
Comprueban las rutas, sus metadatos y carga diferida, los títulos, el nombre público,
los enlaces activos, el menú y el foco. Cada prueba crea un historial en memoria
independiente. Las pruebas PHP validan enlaces directos, rutas reservadas y métodos
del fallback sin exigir un build previo. Swagger conserva su entrada independiente.

## Sincronización del catálogo

La API consulta los datos almacenados localmente. Antes de utilizar los endpoints
de personajes y favoritos se debe ejecutar una sincronización completa:

```sh
./vendor/bin/sail artisan rick-and-morty:sync
```

El comando recorre las páginas de localizaciones, episodios y personajes de la
API externa. Las escrituras son idempotentes, se ejecutan por lotes y el comando
impide que dos sincronizaciones coincidan. Puede repetirse para actualizar el
catálogo sin duplicar registros.

## Documentación y Swagger UI

La especificación OpenAPI 3.1 es la fuente de verdad del contrato HTTP:

- Swagger UI: [http://localhost/docs](http://localhost/docs)
- Documento YAML: [http://localhost/docs/openapi.yaml](http://localhost/docs/openapi.yaml)
- Fichero fuente: `docs/openapi.yaml`

Swagger permite ejecutar peticiones contra el entorno local. El flujo para las
operaciones autenticadas es:

1. Ejecutar `GET /api/auth/csrf-cookie` para crear la cookie CSRF.
2. Ejecutar `POST /api/auth/register` o `POST /api/auth/login`.
3. Probar los endpoints privados de favoritos o cerrar la sesión.

La interfaz envía las cookies con cada petición y copia automáticamente
`XSRF-TOKEN` en la cabecera `X-XSRF-TOKEN`. El token de acceso nunca aparece en
la respuesta JSON: el navegador lo conserva en la cookie `auth_token` con
`HttpOnly`.

Para validar el documento y compilar Swagger UI:

```sh
npm run docs:lint
npm run build
```

## Uso desde Vue y Axios

El futuro cliente Vue debe permitir credenciales y preparar el CSRF antes de
modificar estado. Si se sirve desde otro origen, `FRONTEND_URL` debe coincidir
exactamente con su URL:

```js
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost/api',
  withCredentials: true,
  withXSRFToken: true,
});

await api.get('/auth/csrf-cookie');
await api.post('/auth/login', {
  email: 'morty@example.com',
  password: 'Portal123',
});
```

En producción se debe usar HTTPS y configurar al menos:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.example.com
FRONTEND_URL=https://app.example.com
AUTH_TOKEN_COOKIE_SECURE=true
AUTH_TOKEN_COOKIE_SAME_SITE=lax
```

## Endpoints disponibles

| Método | Ruta | Acceso | Descripción |
| --- | --- | --- | --- |
| `GET` | `/api/auth/csrf-cookie` | Público | Prepara la protección CSRF. |
| `POST` | `/api/auth/register` | Público + CSRF | Registra al usuario e inicia sesión. |
| `POST` | `/api/auth/login` | Público + CSRF | Inicia sesión. |
| `GET` | `/api/auth/user` | Autenticado | Devuelve el usuario actual. |
| `POST` | `/api/auth/logout` | Autenticado + CSRF | Revoca la sesión. |
| `GET` | `/api/characters` | Público | Lista y filtra personajes. |
| `GET` | `/api/characters/{externalId}` | Público | Devuelve el detalle de un personaje. |
| `GET` | `/api/favorites` | Autenticado | Lista los favoritos paginados. |
| `PUT` | `/api/favorites/{externalId}` | Autenticado + CSRF | Añade un favorito de forma idempotente. |
| `DELETE` | `/api/favorites/{externalId}` | Autenticado + CSRF | Elimina un favorito de forma idempotente. |

`GET /api/characters` admite `name`, `status`, `species`, `gender`, `page` y
`per_page`. Los listados usan 20 elementos por defecto y aceptan un máximo de
100. Todos los detalles de parámetros, respuestas, códigos de error y ejemplos
están definidos en OpenAPI.

## Arquitectura y decisiones técnicas

- `app/Domain` contiene contratos, DTO, excepciones y representaciones del dominio.
- `app/Services` coordina los casos de uso y las integraciones externas.
- Los controladores se limitan al transporte HTTP.
- La autenticación usa controladores de acción única para registro, login, logout,
  usuario actual y cookie CSRF. `AuthenticationResponseFactory`, en la capa HTTP,
  centraliza la respuesta JSON y la cookie privada de registro y login.
- Los modelos Eloquent se ocupan de la persistencia y las relaciones.
- El proveedor externo se abstrae mediante contratos para que pueda sustituirse y probarse sin red.
- La autenticación usa tokens opacos propios. Solo se persiste su huella SHA-256 y el valor original viaja en una cookie `HttpOnly`.
- `app/Services/Authentication` reúne registro, login y ciclo de vida de tokens:
  generación (`TokenGenerator`), validación de solo
  lectura (`TokenValidator`), actividad (`TokenUsageRecorder`) y revocación
  (`TokenRevocationService`). El middleware registra uso solo tras validar el token.
  Registrar uso no prolonga la caducidad; no existe renovación de tokens.
- `Services/Authentication` contiene `RegisterUserService` y `LoginService`.
  Ambos delegan la emisión y el registro conserva una transacción para usuario y token.
- `Services/Favorites` separa listado, alta y eliminación en servicios específicos,
  manteniendo el aislamiento por usuario y la idempotencia de las mutaciones.
- Los servicios de listado reciben la página explícitamente. Los controladores
  pasan el valor validado y las colecciones HTTP conservan los filtros en los
  enlaces; los servicios no copian parámetros de la petición.
- Los DTOs son clases `readonly` con propiedades promovidas, conservando tipos,
  nombres de argumentos y PHPDoc de propiedades y parámetros.
- `Services/RickAndMorty/Mapping` separa los mappers de personajes, episodios,
  localizaciones y páginas; `ResponsePayloadReader` concentra las validaciones comunes.
  `RetryPolicy` decide los errores recuperables y la espera de `Retry-After`.
- Los persistidores de personajes, episodios y localizaciones residen en sus
  carpetas funcionales. El coordinador del catálogo resuelve referencias, agrega
  resultados y mantiene una única transacción global: un fallo revierte también
  los recursos y relaciones guardados anteriormente.
- El coordinador organiza sus recorridos en métodos privados por recurso; esta
  separación no añade capas ni cambia las consultas o los contadores.
- Los mensajes de las excepciones propias están en castellano. Los nombres de
  campos externos, códigos y etapas técnicas se conservan para el diagnóstico.
- Las peticiones que modifican estado usan CSRF de doble envío y los intentos de autenticación tienen limitación de frecuencia.
- Los errores siguen el formato común `{ "error": { "code", "message", "details" } }`.

## Calidad y pruebas

Las pruebas de cada mapper están separadas en `tests/Unit/RickAndMorty/Mapping`.
Comparten únicamente los datos externos de `tests/Support/RickAndMortyPayloads`.
`tests/Unit/Domain` comprueba el contenido y la inmutabilidad de los DTOs sin
depender de los transformadores.

```sh
# Suite completa
./vendor/bin/sail artisan test

# Sintaxis de un fichero PHP modificado
./vendor/bin/sail php -l app/Ruta/Al/Fichero.php

# Formato PHP
./vendor/bin/sail pint --test

# Contrato OpenAPI y recursos frontend
npm test
npm run docs:lint
npm run build
```

GitHub Actions ejecuta la suite en PHP 8.2, 8.3 y 8.4 con SQLite. La comprobación
de frontend ejecuta las pruebas Vue, valida OpenAPI y compila la SPA y Swagger UI.
El hook local `pre-push`
ejecuta las pruebas de Laravel mediante Sail y cancela el envío si fallan.
