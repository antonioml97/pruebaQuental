# API de Rick and Morty

Backend REST desarrollado con Laravel 12 para sincronizar el catálogo público de
Rick and Morty, consultarlo con filtros y gestionar los personajes favoritos de
cada usuario. Incluye una SPA Vue 3 funcional, compilada con Vite y estilizada con
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
git clone https://github.com/antonioml97/pruebaQuental.git
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
catálogo en `/characters`. Vue Router 4 utiliza historial HTML5 y
carga cada vista de forma diferida, dentro de un layout compartido con navegación
adaptable a móvil. Al arrancar recupera el usuario mediante la API y mantiene el
estado de sesión. Incluye registro, login, logout y guardas de navegación. Las
consultas de catálogo incluyen filtros y paginación, y cada personaje dispone de
una ficha pública. Los usuarios autenticados pueden guardar y quitar favoritos
desde el catálogo, el detalle y su listado privado.

| URL | Vista | Acceso previsto |
| --- | --- | --- |
| `/characters` | Catálogo con filtros, paginación y estados de carga/error/vacío. | Público. |
| `/characters/:externalId` | Ficha con datos, localizaciones y episodios; identificador público entero positivo. | Público. |
| `/login` | Inicio de sesión y recuperación del destino original. | Invitado. |
| `/register` | Registro con inicio de sesión automático. | Invitado. |
| `/favorites` | Favoritos privados con paginación y acciones de eliminación. | Autenticado. |
| Cualquier otra ruta del cliente | Página no encontrada. | Público. |

Los metadatos `title` y `access` centralizan el título y el acceso previsto de
cada ruta. Las guardas esperan a la sesión pendiente: un invitado que abre
favoritos termina en login y un autenticado que abre login o registro vuelve al
catálogo. **Las guardas del cliente no sustituyen la autorización de la API.**
El layout actualiza el título del documento y lleva
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
`resources/js/domains/characters/views/CharactersView.vue` debe verse sin recargar manualmente.
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
| `app.js`, `bootstrap.js` y `App.vue` | Composición, montaje y recuperación inicial de sesión. |
| `domains/authentication/` | Componentes, composables, servicios y vistas de registro/login. `index.js` expone el servicio público del dominio. |
| `domains/characters/` | Catálogo y detalle: componentes de presentación, composables de carga/cancelación, servicio HTTP y vistas diferidas. |
| `domains/favorites/` | Servicio HTTP, estado privado compartido, botón, avisos y vista de favoritos. |
| `shared/components/` | Presentación transversal: `BrandMark` y `PagePlaceholder`. |
| `shared/layouts/` | `MainLayout.vue`: cabecera, navegación, contenido y pie compartidos. |
| `shared/views/` | Página 404, que no pertenece a un dominio funcional. |
| `router/` | Mapa de rutas, guardas de sesión y validación del destino posterior al login. |
| `shared/composables/` | Sesión compartida por todos los dominios, sin Pinia. |
| `shared/services/http/` | Instancia Axios y normalización de errores/cancelaciones. |
| `shared/utils/` | Utilidades puras como `removeEmptyParams`, que elimina `''`, `null` y `undefined` sin mutar el objeto. |

`resources/js/` es la raíz de fuentes de la SPA, equivalente a `src/` en un
proyecto Vue independiente. Se conservan las entradas de Laravel/Vite. Los módulos
de cada dominio utilizan las piezas transversales de `shared`; la sesión recibe
el servicio de autenticación desde `bootstrap.js`, sin importar ese dominio.
El router carga las vistas directamente de forma diferida; el índice de
autenticación no reexporta pantallas ni incorpora sus componentes al arranque.

Las carpetas previstas se crearán al incorporar su primer módulo; no se añaden
archivos vacíos ni implementaciones ficticias. Los componentes usan Composition
API con `<script setup>`. `resources/css/app.css` centraliza el tema de Tailwind:
colores, tipografía del sistema, foco visible y la escala de espaciado de Tailwind.
No se descargan fuentes externas.
Tailwind registra explícitamente las fuentes Blade, JavaScript y Vue; no escanea
todo el repositorio ni las vistas compiladas en `storage`.

### Catálogo y búsquedas compartibles

`/characters` consulta `GET /api/characters` mediante el mismo cliente Axios
configurado en el arranque. No exige una sesión ni consulta directamente al
proveedor externo. La base debe contener el catálogo sincronizado para mostrar
personajes; un listado vacío no ejecuta automáticamente la sincronización.

- Aplica nombre, estado, especie y género con «Buscar personajes» o Enter.
  Los textos se editan localmente hasta enviar, sin peticiones ni entradas de
  historial por cada tecla. «Limpiar filtros» restablece la búsqueda.
- Filtros y página se guardan en la query de Vue Router. Recargar, compartir la
  URL y usar atrás/adelante recupera la consulta. Cambiar filtros vuelve a página 1.
- Estado y género muestran etiquetas en castellano pero envían los valores del
  contrato: `Alive`, `Dead`, `unknown`, `Female`, `Male` y `Genderless`.
  Nombre y especie buscan sobre los textos originales del catálogo.
- Se admite `per_page` de 1 a 100 en la URL (20 por defecto). Los parámetros
  desconocidos, repetidos o inválidos se normalizan mediante `replace`, sin
  añadir otra entrada al historial; los valores predeterminados se omiten.
- Cada cambio cancela la petición anterior y descarta cualquier respuesta o
  error obsoleto, aunque el transporte no llegue a cancelarse. Los resultados
  anteriores se ocultan durante la nueva carga.
- La navegación usa `links` para habilitar controles y `meta` para páginas y
  totales. Las URLs recibidas del servidor nunca se siguen directamente: se
  navega dentro de la SPA y se consulta el endpoint configurado.
- Se anuncian carga y resultados; los errores ofrecen un reintento manual y las
  páginas vacías fuera de rango permiten volver a la primera. Las tarjetas
  incluyen texto alternativo, reserva de espacio y sustituto de imágenes fallidas.

Ejemplo: `/characters?name=Rick&status=Alive&page=2&per_page=7`.
Las tarjetas enlazan a la ficha conservando los filtros y la página en su URL.

### Detalle público del personaje

`/characters/:externalId` consulta `GET /api/characters/{externalId}` sin exigir
login. Usa el identificador público del personaje, nunca la clave local de la base.

- `createCharacterService.detail` valida el identificador y la respuesta;
  `useCharacterDetail` gestiona carga, error y cancelación. Cambiar de personaje
  o salir cancela e invalida la petición anterior, sin reintentos automáticos.
- `CharacterProfile` presenta imagen y atributos. Comparte `CharacterImage` y
  `CharacterFacts` con las tarjetas para conservar las etiquetas y alternativas.
- Origen y localización actual se muestran por separado. Las relaciones nulas
  y los valores `unknown` tienen una explicación en castellano.
- Los episodios incluyen nombre, código y fecha de emisión en castellano.
  Se dibujan inicialmente 20; «Mostrar más episodios» añade otros 20 y lleva el
  foco al primer elemento nuevo. La API entrega la lista completa: no se añade
  una paginación de servidor ni se hacen nuevas peticiones al desplegarla.
- «Volver a personajes» reconstruye una ruta interna con los filtros y página
  de la URL. Funciona también al recargar o abrir la ficha en otra pestaña.
  Solo acepta parámetros de búsqueda conocidos, no destinos externos.
- Se distinguen carga, personaje inexistente (404) y otros fallos con reintento
  manual. El enlace de regreso permanece disponible en todos esos estados.

Los slots `actions` de `CharacterProfile` y `CharacterCard` incorporan el botón
de favoritos sin acoplar esos componentes de presentación a la sesión.

### Favoritos privados

Inicia sesión y usa «Añadir a favoritos» desde una tarjeta o una ficha. La misma
acción pasa a «Quitar de favoritos» tras la confirmación del servidor, y el estado
se comparte con `/favorites` sin recargar la colección al navegar.

- `createFavoriteService` usa el cliente Axios existente. Envía `PUT` para añadir
  y `DELETE` para quitar, preparando la cookie CSRF antes de cada escritura.
- `createFavorites` mantiene una colección por aplicación y sesión, sin Pinia,
  `localStorage` ni dependencias nuevas. La API no devuelve `is_favorite`, por lo
  que se leen sus páginas de 100 elementos una vez al recuperar la sesión. No se
  hace una petición por tarjeta ni se cambia el contrato del backend.
- El listado presenta esa colección en páginas locales de 20 elementos. La URL
  conserva `page`; si se elimina el último personaje de una página, se vuelve a
  la última página disponible. Los cambios hechos desde otra pestaña se recuperan
  al recargar: no hay sincronización entre pestañas ni sondeo automático.
- Mientras se carga la colección no se asume que un personaje no sea favorito.
  Las escrituras se realizan de una en una, con controles desactivados y avisos
  accesibles. No se cambia el estado visual antes de recibir confirmación.
- Cambiar o cerrar sesión borra e invalida los datos y peticiones anteriores.
  Un 401 vigente caduca la sesión y lleva a login si la ruta es privada. Ante 419
  se ofrece «Renovar CSRF y reintentar», sin reenvíos automáticos.
- Las respuestas de red antiguas no pueden rellenar los favoritos de otra cuenta.
  Las lecturas y escrituras siguen siendo autorizadas por el backend.

### Variables públicas

- `VITE_APP_NAME`: nombre de la cabecera y sufijo del título. `.env.example` lo toma de
  `APP_NAME`; si queda vacío, se muestra «Rick and Morty».
- `VITE_API_BASE_URL`: URL base pública de la API, `/api` por defecto. Incluye el
  prefijo de API, no el de un endpoint. No debe contener credenciales ni secretos.
- `APP_URL`: origen de Laravel (por defecto `http://localhost`), configuración del
  servidor, no una variable pública de JavaScript.
- `FRONTEND_URL`: origen permitido por CORS si se utiliza un cliente separado.
  El puerto de Vite sirve recursos; no cambia el origen de la SPA entregada por Laravel.

El valor relativo `/api` conserva el origen de Laravel y evita CORS en la instalación
habitual. Todo valor con prefijo `VITE_` queda expuesto en el cliente:
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
El cliente HTTP se comprueba con adaptadores Axios simulados y un transporte XHR
simulado para verificar la cabecera CSRF y `withCredentials`. Las pruebas de
sesión cubren éxito, 401, 419, 422, errores de red, cancelación y concurrencia;
las de arranque no requieren cuentas reales ni conexión al backend.
Se comprueban también formularios, foco y etiquetas, envíos duplicados, guardas,
redirecciones seguras y recuperación manual de CSRF al entrar o salir.

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

El cliente explícito de `shared/services/http` utiliza `withCredentials: true`,
`withXSRFToken: true`, `Accept: application/json` y un timeout de 10 segundos.
Solo admite destinos dentro de la base configurada, evitando enviar la cabecera
CSRF por accidente a otra URL. No se instala Axios en `window`.

El servicio de autenticación solicita `GET /api/auth/csrf-cookie` antes de cada
registro, login o logout; las lecturas de usuario no necesitan CSRF. Solo devuelve
los campos públicos `id`, `name` y `email`. La cookie de autenticación permanece
en el navegador, inaccesible a JavaScript; no hay renovación de tokens.

`bootstrap.js` crea una sesión por aplicación y la proporciona a las vistas.
Inicia `GET /api/auth/user` antes de la navegación inicial para que una guarda
privada pueda esperar sin bloquear el arranque. Monta el layout mientras se
resuelve la ruta y mantiene las páginas públicas independientes de la red. `useSession()`
expone referencias de solo lectura `status` (`loading`, `authenticated`, `guest`),
`user`, `error` e `isAuthenticated`, además de `restore`, `login`, `register` y `logout`.

- Un 401 de restauración o logout deja el estado como invitado sin bucles. El 401
  de login conserva el mensaje de credenciales incorrectas.
- Otros errores conservan la identidad anterior, si la había, y quedan disponibles
  en `session.error`; una avería de red no demuestra que la cookie haya caducado.
- Las restauraciones simultáneas comparten la petición. Mientras hay una operación
  pendiente, otra operación de sesión se rechaza con `session_busy`, evitando
  respuestas que compitan por la cookie. La UI respeta `loading` y las guardas
  esperan mediante `whenIdle()` sin iniciar consultas adicionales.
- Se aceptan opciones `{ signal }` con `AbortController`. Cancelar no cierra la sesión
  ni garantiza deshacer una escritura que ya llegó al servidor; `restore()` permite
  reconciliar el estado. La señal de la primera restauración controla la petición compartida.
- No se usan localStorage, sessionStorage ni un almacén de tokens. Un fallo de arranque
  queda en `session.error`; se puede reintentar explícitamente con `restore()`.

Ejemplo de uso dentro del `setup` de una vista bajo `domains/authentication/views`:

```js
import { useSession } from '../../../shared/composables/useSession';

const session = useSession();
// Tras comprobar que session.status.value no es 'loading':
try {
  await session.login({ email, password });
} catch (error) {
  // error.details.email conserva los mensajes por campo de un 422.
  // error.code distingue credenciales incorrectas, CSRF, red, etc.
}
```

El error HTTP normalizado conserva `code`, `message`, `details`, `status`, `data`
y `headers` (por ejemplo, `Retry-After`), pero no la configuración de la petición
que podría contener una contraseña. Las cancelaciones permanecen identificables
con `isRequestCancelled`. Ni un 401 ni un 419 provocan reenvíos automáticos de
mutaciones; cada consumidor recibe el error para decidir cómo presentarlo.

### Registro, login y logout

- El registro pide nombre, correo, contraseña y confirmación. Valida presencia,
  formato básico de correo, longitud mínima y coincidencia de contraseñas; Laravel
  sigue siendo la autoridad sobre unicidad, fortaleza y el resto de reglas.
- El login valida correo y contraseña obligatorios, sin exigir reglas de registro
  a una contraseña existente. Tras entrar vuelve al destino indicado en `redirect`
  solo si es una ruta conocida de la SPA que no sea login/registro. URLs externas,
  destinos inválidos y parámetros repetidos se descartan a favor del catálogo.
- El registro siempre termina en el catálogo. El layout muestra la identidad pública
  y «Cerrar sesión» en lugar de los enlaces de acceso y registro.
- Los envíos desactivan el formulario y bloquean la salida de esa pantalla mientras
  está pendiente la escritura. No se aborta una mutación al cambiar de ruta, porque
  abortar la petición no garantiza deshacer la cookie que haya emitido el servidor.
- Los errores de campo se vinculan con `aria-describedby` y `aria-invalid`. Un
  resumen anunciado recibe el foco y permite saltar a cada campo; las contraseñas
  se borran tras el éxito o al desmontar la vista.
- Ante un 419 se muestra «Renovar CSRF y reintentar». Ese nuevo envío, iniciado por
  la persona, prepara CSRF antes del POST. Un segundo 419 vuelve a mostrarse sin
  bucles ni reenvíos automáticos. Logout ofrece el mismo mecanismo.
- Logout revoca la sesión mediante la API y lleva al catálogo. Si falla por red o
  CSRF, conserva la identidad anterior y permite reintentar; si devuelve 401, la
  sesión ya no existe y se considera cerrada.

Para probarlo en el navegador: abre `/register`, crea una cuenta, visita `/favorites`,
recarga y cierra sesión desde el menú. Al abrir favoritos de nuevo se solicitará
login y, tras entrar, volverás a esa ruta. Guarda personajes desde el catálogo
para comprobar también el listado privado, el detalle y la eliminación.

Para un cliente separado, `FRONTEND_URL` debe coincidir con su origen permitido.
La configuración CORS por sí sola no comparte cookies entre dominios: la SPA
necesita poder leer la cookie CSRF del backend. La instalación soportada sirve
ambos desde Laravel; si se separan dominios, es necesario un proxy de mismo origen
o revisar expresamente el alcance y las políticas de cookies del despliegue.

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

La revisión final del backend y la SPA, con resultados, alcance y pasos para
repetirla, está en [Validación de entrega](docs/validacion-entrega.md).
