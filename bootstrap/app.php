<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request as HttpRequest;
use App\Http\Middleware\SecurityHeaders;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $trustedHosts = array_values(array_filter(array_map(
            static fn (string $host): string => trim($host),
            explode(',', (string) env('TRUSTED_HOSTS', ''))
        )));

        $trustedProxyHeaders = match (strtoupper((string) env('TRUSTED_PROXY_HEADERS', ''))) {
            'HEADER_X_FORWARDED_AWS_ELB' => HttpRequest::HEADER_X_FORWARDED_AWS_ELB,
            'HEADER_FORWARDED' => HttpRequest::HEADER_FORWARDED,
            'HEADER_X_FORWARDED_FOR' => HttpRequest::HEADER_X_FORWARDED_FOR,
            'HEADER_X_FORWARDED_HOST' => HttpRequest::HEADER_X_FORWARDED_HOST,
            'HEADER_X_FORWARDED_PORT' => HttpRequest::HEADER_X_FORWARDED_PORT,
            'HEADER_X_FORWARDED_PROTO' => HttpRequest::HEADER_X_FORWARDED_PROTO,
            'HEADER_X_FORWARDED_PREFIX' => HttpRequest::HEADER_X_FORWARDED_PREFIX,
            default => HttpRequest::HEADER_X_FORWARDED_FOR
                | HttpRequest::HEADER_X_FORWARDED_HOST
                | HttpRequest::HEADER_X_FORWARDED_PORT
                | HttpRequest::HEADER_X_FORWARDED_PROTO
                | HttpRequest::HEADER_X_FORWARDED_PREFIX
                | HttpRequest::HEADER_X_FORWARDED_AWS_ELB,
        };

        $middleware->trustHosts(
            at: static fn (): array => $trustedHosts,
            subdomains: true,
        );

        $trustedProxies = trim((string) env('TRUSTED_PROXIES', ''));
        $middleware->trustProxies(
            at: $trustedProxies !== '' ? $trustedProxies : null,
            headers: $trustedProxyHeaders,
        );

        $middleware->web(append: [
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
