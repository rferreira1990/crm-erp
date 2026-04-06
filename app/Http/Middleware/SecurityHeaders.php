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

        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; "
            . "base-uri 'self'; "
            . "frame-ancestors 'self'; "
            . "form-action 'self'; "
            . "object-src 'none'; "
            . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; "
            . "style-src 'self' 'unsafe-inline' https:; "
            . "img-src 'self' data: blob: https:; "
            . "font-src 'self' data: https:; "
            . "connect-src 'self' https:"
        );

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
