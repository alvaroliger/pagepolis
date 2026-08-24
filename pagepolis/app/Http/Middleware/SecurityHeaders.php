<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        // SAMEORIGIN, no DENY: la app embebe su propio contenido en iframes
        // del MISMO origen (la vista previa de plantillas en /plantillas/{id}/render
        // y la previsualización de proyectos). Con DENY el navegador bloqueaba ese
        // iframe con "rechazó la conexión" y la preview salía en blanco. SAMEORIGIN
        // sigue impidiendo que CUALQUIER otro sitio nos meta en un iframe (clickjacking).
        // No se pone un CSP global aquí a propósito: ProjectController::preview y
        // SiteController fijan su propio CSP por respuesta y un set() global lo pisaría.
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}
