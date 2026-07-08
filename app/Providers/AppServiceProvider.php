<?php

namespace App\Providers;

use App\Services\GoogleTokenVerifier;
use Google\Client as GoogleClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GoogleTokenVerifier::class, function (): GoogleTokenVerifier {
            $client = new GoogleClient(["client_id" => config("services.google.client_id")]);

            return new GoogleTokenVerifier($client);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for("auth", function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->ip() ?? "unknown");
        });

        RateLimiter::for("verification", function (Request $request): Limit {
            return Limit::perMinute(5)->by($request->user()->id ?? $request->ip() ?? "unknown");
        });
    }
}
