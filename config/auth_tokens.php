<?php

declare(strict_types=1);

/**
 * Configura la autenticación propia mediante cookies y tokens opacos.
 */

return [
    'lifetime_minutes' => (int) env('AUTH_TOKEN_LIFETIME_MINUTES', 120),

    'cookie' => [
        'name' => env('AUTH_TOKEN_COOKIE_NAME', 'auth_token'),
        'secure' => filter_var(env('AUTH_TOKEN_COOKIE_SECURE', false), FILTER_VALIDATE_BOOL),
        'same_site' => env('AUTH_TOKEN_COOKIE_SAME_SITE', 'lax'),
    ],

    'csrf_cookie' => [
        'name' => 'XSRF-TOKEN',
        'header' => 'X-XSRF-TOKEN',
    ],
];
