# Validación de entrega

Revisión del 2 de septiembre de 2026 para los issues #10 y #32, sobre el código
integrado hasta `302d485`, en la rama `codex/legibilidad-backend`.
Esta entrega documenta verificaciones; no incorpora nuevas funcionalidades,
dependencias ni refactorizaciones.

## Backend

| Comprobación | Resultado y evidencia |
| --- | --- |
| Entorno | Sail en ejecución y MySQL 8.4 `healthy`. |
| Instalación del esquema | Nueve migraciones aplicadas sobre un esquema MySQL temporal inicialmente vacío, mediante Artisan dentro de Sail. Segunda ejecución: `Nothing to migrate`. |
| Suite completa | 120 pruebas y 540 aserciones correctas con Sail y MySQL. |
| Sincronización | La suite verifica repetición idempotente, actualización de relaciones, fallos de descarga y rollback, incluido un fallo tardío de persistencia. |
| Aislamiento externo | Los casos del cliente usan respuestas HTTP simuladas y `preventStrayRequests`; la sincronización sustituye el contrato del cliente por un doble. No se necesita la API externa para pasar la suite. |
| Formato | `sail pint --test`: 244 archivos correctos. |
| PHPDoc | Revisión de los 72 archivos de `app`: bloques de archivo/clase, miembros propios y documentación de parámetros de métodos sin incidencias. |
| Dependencias | `composer validate --strict` correcto y `composer audit --locked` sin avisos de vulnerabilidad. |
| Secretos | Revisión de archivos versionados y patrones de claves privadas/tokens sin hallazgos. `.env`, credenciales, cuaderno local y artefactos no se versionan. No sustituye una auditoría de seguridad especializada. |

### Prueba negativa del hook

Se utilizó el hook real de `.husky/_/pre-push`, que ejecuta
`.husky/pre-push`, sin modificar ni desactivar Husky:

1. Crear un repositorio bare temporal local, sin conexión con GitHub.
2. Añadir temporalmente un test con una aserción fallida explícita.
3. Intentar publicar `HEAD` en ese repositorio local.
4. Resultado: `1 failed, 120 passed`, `husky - pre-push script failed (code 1)`
   y rechazo de la subida. `show-ref` confirma que no se publicó ninguna referencia.
5. Retirar el test temporal y repetir la suite: 120 pruebas correctas.

El test fallido no forma parte de ningún commit. Las subidas normales vuelven a
ejecutar el mismo hook, comprobando también su camino correcto.

## Frontend: pruebas y compilación

| Comprobación | Resultado |
| --- | --- |
| Instalación reproducible | `sail npm ci` correcto; auditoría npm sin vulnerabilidades conocidas. |
| Pruebas | 248 pruebas correctas en 16 archivos de `tests/Frontend`. |
| Contrato | `sail npm run docs:lint` correcto. |
| Producción | `sail npm run build` correcto, con minificación de Vite y vistas diferidas. |
| Separación de bundles | Entrada principal de unos 162,74 kB (61,77 kB gzip); Swagger permanece en una entrada independiente. |
| CI | El workflow `Tests` incluye `npm ci`, pruebas Vue, OpenAPI y build; además prueba PHP 8.2, 8.3 y 8.4 con SQLite. El PR de esta revisión debe pasar estas comprobaciones antes de fusionarse. |

Se conserva el aviso conocido del bundle de Swagger, de unos 1.680,86 kB:
no se descarga desde la SPA y no bloquea la compilación. La instalación local
también informa de scripts de instalación bloqueados por su política de npm;
las pruebas y el build terminan correctamente sin cambiar dicha política.

### Estados y regresiones

| Estado o caso | Evidencia |
| --- | --- |
| Carga y escritura pendiente | Pruebas de catálogo/detalle/favoritos y formulario real bloqueado durante el registro. |
| Vacío | Catálogo real sin registros antes de preparar datos QA y favoritos vacíos tras eliminar el último personaje. |
| 401 | Login real con contraseña incorrecta, mensaje en castellano y foco en el aviso. Guardas y pérdida de sesión cubiertas por pruebas. |
| 404 | Apertura directa de un identificador inexistente: pantalla explicativa con regreso al catálogo; pruebas de detalle y rutas desconocidas. |
| 419 | Pruebas de formularios y favoritos: renovación CSRF y reintento explícito, sin reenvío automático ni pérdida injustificada de identidad. |
| 422 y 429 | Pruebas de formularios, catálogo y cliente HTTP: mensaje, conservación del correo, errores de campo cuando proceden y ausencia de reintentos automáticos. |
| Error de red/servidor | Pruebas de error y recuperación manual. |
| Concurrencia | Pruebas de cancelación, respuestas obsoletas, cambios de cuenta, bloqueo mutuo de altas/bajas y eliminación pendiente. |

Los casos 419/422/429 se verifican con respuestas controladas en la suite, no
forzando límites de acceso sobre cuentas de desarrollo.

## Recorrido interactivo contra la API local

Verificado con Chromium automatizado sobre `http://localhost`, servido por
Laravel con los archivos compilados de producción, sin servidor Vite.
No se simularon las respuestas de API de este recorrido.

Se prepararon 25 personajes QA, una localización y un episodio, y se registró
una cuenta temporal. No se sobrescribieron registros existentes. Los datos QA
y la cuenta se eliminaron al terminar; las capturas y herramientas auxiliares
permanecen fuera del repositorio.

1. Registro desde `/register`: inicio de sesión y regreso al catálogo.
2. Catálogo de 25 personajes: segunda página con cinco elementos y controles
   de avance desactivados en el final.
3. Búsqueda conjunta por nombre, estado, especie y género, enviada con Enter:
   reinicio a página 1 y consulta conservada en la URL.
4. Alta desde una tarjeta: confirmación visual y una relación en MySQL.
5. Ficha con origen, ubicación actual y episodio; mismo favorito marcado.
6. Recarga directa de la ficha: conserva sesión, favorito y parámetros de búsqueda.
7. Listado privado: muestra el personaje; eliminación confirmada y estado vacío.
8. Logout y apertura directa de favoritos: redirección a login.
9. Credenciales incorrectas: error accesible, sin redirección ni pérdida del correo.
10. Login correcto: regreso a favoritos, destino original solicitado.
11. Cierre de sesión, ficha inexistente y comprobación independiente de `/docs`.

### Accesibilidad y responsive

- Catálogo y ficha revisados visualmente a 390 × 844, 768 × 1024 y 1440 × 1000.
  No se observa desbordamiento horizontal; controles, tarjetas y relaciones
  se distribuyen según el ancho disponible. Favoritos y login se revisan también en móvil.
- Formularios con etiquetas asociadas; imágenes del catálogo y detalle con
  texto alternativo. Las pruebas incluyen la alternativa ante imágenes fallidas.
- El enlace «Saltar al contenido» lleva el foco a `main-content`.
- Menú móvil operable con Enter y cierre mediante Escape, que devuelve el foco
  al botón. Se comprobó el contorno visible de foco.
- El error de login recibe el foco; la eliminación del último favorito deja
  el foco en el título de la página. La suite cubre navegación y foco entre rutas.
- Contrastes calculados para la paleta principal: texto/fondo 13,79:1,
  texto secundario/fondo 6,14:1, blanco/botón oscuro 12,03:1 y verde/fondo 6,43:1.
  Esta revisión no equivale a una certificación exhaustiva de accesibilidad.

### Consola y datos sensibles

La herramienta de navegador no registró excepciones de página ni mensajes de
consola durante el recorrido revisado. No hay llamadas a `console.log`,
`console.warn`, `console.error` o `console.debug` en las fuentes cliente.
Se comprobó que `auth_token` no es visible en `document.cookie` y que
`localStorage` y `sessionStorage` están vacíos. No se guardaron estados de
autenticación ni trazas con credenciales. Swagger no figura entre los recursos
descargados por la SPA.

## Repetir la revisión

Seguir primero la instalación del README. Desde WSL2, con Sail iniciado:

```sh
./vendor/bin/sail ps
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test
./vendor/bin/sail pint --test
./vendor/bin/sail composer validate --strict
./vendor/bin/sail composer audit --locked
./vendor/bin/sail npm ci
./vendor/bin/sail npm test
./vendor/bin/sail npm run docs:lint
./vendor/bin/sail npm run build
git diff --check
git status --short
```

Para comprobar migraciones desde cero, utilizar exclusivamente un esquema
temporal nuevo o una instalación desechable: **no ejecutar `migrate:fresh` sobre
la base de desarrollo con datos que se quieran conservar**. En esta revisión se
creó un esquema `testing_delivery_*`, se seleccionó mediante una conexión
temporal del proceso de Artisan y se eliminó solo ese esquema al finalizar.

Repetir después el recorrido anterior con una cuenta de prueba y datos
identificables. Verificar teclado y los tres tamaños, comprobar `/docs` y
retirar solo los registros creados para la comprobación. Los artefactos de
`tmp/` son locales; se excluyen mediante `.git/info/exclude`, sin borrarlos.
