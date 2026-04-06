<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->buildContentSecurityPolicy());

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function buildContentSecurityPolicy(): string
    {
        $scriptSrc = [
            "'self'",
            "'unsafe-inline'",
            'https://maps.googleapis.com',
        ];

        $styleSrc = [
            "'self'",
            "'unsafe-inline'",
            'https://fonts.googleapis.com',
            'https://fonts.bunny.net',
        ];

        $imgSrc = [
            "'self'",
            'data:',
            'blob:',
            'https://maps.googleapis.com',
            'https://maps.gstatic.com',
        ];

        $fontSrc = [
            "'self'",
            'data:',
            'https://fonts.gstatic.com',
            'https://fonts.bunny.net',
        ];

        $connectSrc = [
            "'self'",
            'https://maps.googleapis.com',
        ];

        if (app()->environment('local')) {
            $scriptSrc = array_merge($scriptSrc, [
                'http://localhost:5173',
                'http://127.0.0.1:5173',
            ]);

            $styleSrc = array_merge($styleSrc, [
                'http://localhost:5173',
                'http://127.0.0.1:5173',
            ]);

            $connectSrc = array_merge($connectSrc, [
                'http://localhost:5173',
                'http://127.0.0.1:5173',
                'ws://localhost:5173',
                'ws://127.0.0.1:5173',
            ]);
        }

        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "object-src 'none'",
            'script-src ' . implode(' ', array_values(array_unique($scriptSrc))),
            'style-src ' . implode(' ', array_values(array_unique($styleSrc))),
            'img-src ' . implode(' ', array_values(array_unique($imgSrc))),
            'font-src ' . implode(' ', array_values(array_unique($fontSrc))),
            'connect-src ' . implode(' ', array_values(array_unique($connectSrc))),
        ];

        if (app()->environment('production')) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }
}
