<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Aplicación de Rick and Morty: base del cliente Vue y acceso a la documentación de la API.">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app"></div>
    <noscript>Activa JavaScript para utilizar la aplicación. Puedes consultar el <a href="{{ route('docs.openapi') }}">contrato de la API</a> sin JavaScript.</noscript>
</body>
</html>
