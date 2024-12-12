<?php

namespace Soap\OmiseWebhooks\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Soap\OmiseWebhooks\OmiseWebhooksServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            OmiseWebhooksServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config(['omise-webhooks.signing_secret' => 'test_signing_secret']);

    }

    protected function setUpDatabase()
    {
        $migration = include __DIR__.'/../vendor/spatie/laravel-webhook-client/database/migrations/create_webhook_calls_table.php.stub';

        $migration->up();
    }

    protected function disableExceptionHandling()
    {
        $this->app->instance(ExceptionHandler::class, new class extends Handler
        {
            public function __construct() {}

            public function report(Exception $e) {}

            public function render($request, Exception $exception)
            {
                throw $exception;
            }
        });
    }

    protected function determineStripeSignature(array $payload, ?string $configKey = null): string
    {
        $secret = ($configKey) ?
            config("omise-webhooks.signing_secret_{$configKey}") :
            config('omise-webhooks.signing_secret');

        $timestamp = time();

        $timestampedPayload = $timestamp.'.'.json_encode($payload);

        $signature = hash_hmac('sha256', $timestampedPayload, $secret);

        return "t={$timestamp},v1={$signature}";
    }
}
