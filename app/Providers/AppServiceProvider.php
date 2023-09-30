<?php

namespace App\Providers;

use App\AmoCrmService;
use App\AmoCrmTokenStore;
use Illuminate\Support\ServiceProvider;
use AmoCRM\Client\AmoCRMApiClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AmoCrmService::class, function () {

            $apiClient = new AmoCRMApiClient(
                env('AMO_ID'),
                env('AMO_KEY'),
                env('AMO_REDIRECT_URL'));

            $apiClient->setAccountBaseDomain(env('AMO_BASE_DOMAIN'));

            $tokenStore = new AmoCrmTokenStore();

            return new AmoCrmService($apiClient, $tokenStore);
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
