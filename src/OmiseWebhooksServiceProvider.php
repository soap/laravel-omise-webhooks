<?php

namespace Soap\OmiseWebhooks;

use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class OmiseWebhooksServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-omise-webhooks')
            ->hasConfigFile();

        Route::macro('omiseWebhooks', function (string $url) {
            Route::post($url, '\Soap\OmiseWebhooks\OmiseWebhooksController');
        });
    }
}
