import SwaggerUIBundle from 'swagger-ui-dist/swagger-ui-bundle';
import SwaggerUIStandalonePreset from 'swagger-ui-dist/swagger-ui-standalone-preset';
import 'swagger-ui-dist/swagger-ui.css';

const container = document.querySelector('#swagger-ui');

if (container) {
    const csrfCookie = () =>
        document.cookie
            .split('; ')
            .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
            ?.slice('XSRF-TOKEN='.length);

    SwaggerUIBundle({
        url: container.dataset.openapiUrl,
        dom_id: '#swagger-ui',
        deepLinking: true,
        displayRequestDuration: true,
        tryItOutEnabled: true,
        persistAuthorization: false,
        presets: [SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset],
        layout: 'StandaloneLayout',
        requestInterceptor(request) {
            request.credentials = 'include';

            if (!['GET', 'HEAD', 'OPTIONS'].includes(request.method?.toUpperCase())) {
                const csrfToken = csrfCookie();

                if (csrfToken) {
                    request.headers['X-XSRF-TOKEN'] = decodeURIComponent(csrfToken);
                }
            }

            return request;
        },
    });
}
