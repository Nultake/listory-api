<?php

namespace App\Providers;

use App\Services\GoogleTokenVerifier;
use Google\Client as GoogleClient;
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
        //
    }
}
