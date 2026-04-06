<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('auth-login', function (Request $request): array {
            $email = Str::lower((string) $request->input('email', 'guest'));

            return [
                Limit::perMinute(5)
                    ->by('login:' . $email . '|' . $request->ip())
                    ->response(fn () => $this->tooManyRequestsResponse('Muitas tentativas de login. Tenta novamente dentro de 1 minuto.')),
                Limit::perMinute(30)
                    ->by('login-ip:' . $request->ip())
                    ->response(fn () => $this->tooManyRequestsResponse('Muitas tentativas de login a partir deste IP.')),
            ];
        });

        RateLimiter::for('search-ajax', function (Request $request): array {
            return [
                Limit::perMinute(90)
                    ->by($this->requestRateKey($request, 'search-user'))
                    ->response(fn () => $this->tooManyRequestsResponse('Pesquisa temporariamente limitada. Tenta novamente em instantes.')),
                Limit::perMinute(240)
                    ->by('search-ip:' . $request->ip())
                    ->response(fn () => $this->tooManyRequestsResponse('Muitas pesquisas a partir deste IP.')),
            ];
        });

        RateLimiter::for('document-export', function (Request $request): array {
            return [
                Limit::perMinute(20)
                    ->by($this->requestRateKey($request, 'export-user'))
                    ->response(fn () => $this->tooManyRequestsResponse('Limite de exportacoes atingido. Tenta novamente em 1 minuto.')),
                Limit::perMinute(60)
                    ->by('export-ip:' . $request->ip())
                    ->response(fn () => $this->tooManyRequestsResponse('Muitas exportacoes a partir deste IP.')),
            ];
        });

        RateLimiter::for('file-upload', function (Request $request): array {
            return [
                Limit::perMinute(20)
                    ->by($this->requestRateKey($request, 'upload-user'))
                    ->response(fn () => $this->tooManyRequestsResponse('Limite de uploads por minuto atingido.')),
                Limit::perMinute(60)
                    ->by('upload-ip:' . $request->ip())
                    ->response(fn () => $this->tooManyRequestsResponse('Muitos uploads a partir deste IP.')),
            ];
        });

        RateLimiter::for('dashboard-heavy', function (Request $request): array {
            return [
                Limit::perMinute(30)
                    ->by($this->requestRateKey($request, 'dashboard-user'))
                    ->response(fn () => $this->tooManyRequestsResponse('Atualizacoes de dashboard limitadas temporariamente.')),
                Limit::perMinute(90)
                    ->by('dashboard-ip:' . $request->ip())
                    ->response(fn () => $this->tooManyRequestsResponse('Muitas consultas de dashboard a partir deste IP.')),
            ];
        });

        RateLimiter::for('item-import', function (Request $request): array {
            return [
                Limit::perMinute(6)
                    ->by($this->requestRateKey($request, 'item-import-user'))
                    ->response(fn () => $this->tooManyRequestsResponse('Limite de importacoes atingido. Aguarda alguns segundos e tenta novamente.')),
                Limit::perMinute(15)
                    ->by('item-import-ip:' . $request->ip())
                    ->response(fn () => $this->tooManyRequestsResponse('Muitas importacoes a partir deste IP.')),
            ];
        });
    }

    private function requestRateKey(Request $request, string $prefix): string
    {
        $userKey = $request->user()
            ? ('u:' . $request->user()->getAuthIdentifier())
            : ('g:' . $request->ip());

        return $prefix . ':' . $userKey . '|ip:' . $request->ip();
    }

    private function tooManyRequestsResponse(string $message)
    {
        if (request()->expectsJson()) {
            return response()->json(['message' => $message], 429);
        }

        return response($message, 429);
    }
}
