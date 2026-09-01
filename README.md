# API de Rick and Morty

Backend REST desarrollado con Laravel 12 para sincronizar el catálogo público de
Rick and Morty, consultarlo con filtros y gestionar los personajes favoritos de
cada usuario. El entorno local se ejecuta con Laravel Sail, PHP y MySQL.

## Requisitos

- Docker Desktop o Docker Engine con Docker Compose.
- Git.
- Node.js y npm para compilar los recursos de Swagger UI y ejecutar sus validaciones.
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
npm install
npm run build
```

`npm install` configura también el hook `pre-push` de Husky. La aplicación queda
disponible en [http://localhost](http://localhost) y su estado se puede comprobar
en [http://localhost/up](http://localhost/up).

Para detener los contenedores:

```sh
./vendor/bin/sail down
```

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
- Los modelos Eloquent se ocupan de la persistencia y las relaciones.
- El proveedor externo se abstrae mediante contratos para que pueda sustituirse y probarse sin red.
- La autenticación usa tokens opacos propios. Solo se persiste su huella SHA-256 y el valor original viaja en una cookie `HttpOnly`.
- Las peticiones que modifican estado usan CSRF de doble envío y los intentos de autenticación tienen limitación de frecuencia.
- Los errores siguen el formato común `{ "error": { "code", "message", "details" } }`.

## Calidad y pruebas

```sh
# Suite completa
./vendor/bin/sail artisan test

# Sintaxis de un fichero PHP modificado
./vendor/bin/sail php -l app/Ruta/Al/Fichero.php

# Formato PHP
./vendor/bin/sail pint --test

# Contrato OpenAPI y recursos frontend
npm run docs:lint
npm run build
```

GitHub Actions ejecuta la suite en PHP 8.2, 8.3 y 8.4 con SQLite. La comprobación
de documentación valida OpenAPI y compila Swagger UI. El hook local `pre-push`
ejecuta las pruebas de Laravel mediante Sail y cancela el envío si fallan.
