# Contexto del proyecto

Este repositorio está en una fase inicial. No se ha definido todavía un framework, una arquitectura ni comandos oficiales de instalación, ejecución o pruebas. No asumas estas decisiones: infiérelas de los archivos del proyecto cuando aparezcan y actualiza este documento si se consolidan nuevas convenciones.

## Forma de trabajar

- Revisa primero la estructura del repositorio y los archivos de configuración existentes.
- Mantén los cambios limitados al objetivo solicitado y respeta las modificaciones locales del usuario.
- No añadas dependencias, frameworks ni herramientas sin una necesidad clara.
- Sigue las convenciones ya presentes en el código. Si todavía no existen, prioriza soluciones sencillas, legibles y mantenibles.
- Añade o actualiza pruebas cuando el cambio introduzca comportamiento verificable.
- Ejecuta las comprobaciones relevantes disponibles antes de dar el trabajo por terminado.
- No incluyas secretos, credenciales ni archivos de entorno con datos sensibles en el repositorio.
- Trata `tmp/` como un directorio de artefactos temporales, no como código fuente.

## Convenciones de commits

- Usa Conventional Commits con prefijos como `feat:`, `fix:` o `chore:` según corresponda.
- Redacta los mensajes de commit en castellano.

## Reglas para PHP

- **Todo fichero PHP debe incluir PHPDoc.** Añade un bloque PHPDoc de nivel de archivo inmediatamente después de `<?php` (y de `declare(strict_types=1);` cuando exista), describiendo brevemente su propósito.
- Documenta también con PHPDoc todas las clases, interfaces, traits, enums, propiedades, métodos y funciones declarados en el fichero.
- Usa etiquetas como `@param`, `@return`, `@throws`, `@var` y `@template` cuando aporten información que no quede suficientemente expresada por los tipos nativos.
- No uses PHPDoc para contradecir los tipos de PHP ni para repetir comentarios obvios. Debe explicar intención, contratos, restricciones o tipos que PHP no pueda expresar.
- Conserva PHPDoc válido y actualizado cuando cambie una firma o el comportamiento documentado.
- Prefiere `declare(strict_types=1);`, tipos explícitos y compatibilidad con la versión de PHP fijada en `composer.json`, cuando dicho archivo exista.
- Sigue PSR-12 salvo que el proyecto incorpore una configuración de estilo diferente.

## Validación

Cuando el proyecto disponga de herramientas, usa los scripts definidos en sus archivos de configuración como fuente de verdad. Para cambios en PHP, comprueba como mínimo la sintaxis de los ficheros modificados con `php -l` y ejecuta las pruebas, el analizador estático y el formateador configurados que correspondan.

## Mantenimiento de este documento

Actualiza `AGENTS.md` cuando se establezcan la arquitectura, los directorios principales, la versión de PHP, los comandos de desarrollo o nuevas convenciones obligatorias.
