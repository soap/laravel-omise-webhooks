# Create webhooks for Omise payment gateway with ease

[![Latest Version on Packagist](https://img.shields.io/packagist/v/soap/laravel-omise-webhooks.svg?style=flat-square)](https://packagist.org/packages/soap/laravel-omise-webhooks)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/soap/laravel-omise-webhooks/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/soap/laravel-omise-webhooks/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/soap/laravel-omise-webhooks/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/soap/laravel-omise-webhooks/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/soap/laravel-omise-webhooks.svg?style=flat-square)](https://packagist.org/packages/soap/laravel-omise-webhooks)

Write simple code to receive webhook calls from Omise payment gateway.

## Support us



## Installation

You can install the package via composer:

```bash
composer require soap/laravel-omise-webhooks
```
The service provider will automatically register itself.

You must publish the config file with:

```bash
php artisan vendor:publish --tag="omise-webhooks-config"
```

This is the contents of the published config file:

```php
return [
      /*

    /*
     * You can define a default job that should be run for all other Omise event type
     * without a job defined in next configuration.
     * You may leave it empty to store the job in database but without processing it.
     */
    'default_job' => '',

    /*
     * You can define the job that should be run when a certain webhook hits your application
     * here. The key is the name of the Omise event type with the `.` replaced by a `_`.
     *
     * You can find a list of Omise webhook types here:
     * https://docs.opn.ooo/api-webhooks#supported-events.
     */
    'jobs' => [
        // 'card_destroy' => \App\Jobs\OmiseWebhooks\HandleCardDestroy::class,
        // 'card_update' => \App\Jobs\OmiseWebhooks\HandleCardUpdate::class,
        
        // 'charge_capture' => \App\Jobs\OmiseWebhooks\HandleChargeCapture::class,
        // 'charge_create' => \App\Jobs\OmiseWebhooks\HandleChargeCreate::class,
        // 'charge_expire' => \App\Jobs\OmiseWebhooks\HandleChargeExpire::class,
        // 'charge_reverse' => \App\Jobs\OmiseWebhooks\HandleChargeReverse::class,
        // 'charge_update' => \App\Jobs\OmiseWebhooks\HandleChargeUpdate::class,

        // 'customer_create' => \App\Jobs\OmiseWebhooks\HandleCustomerCreate::class,
        // 'customer_update' => \App\Jobs\OmiseWebhooks\HandleCustomerUpdate::class,
        // 'customer_destroy' => \App\Jobs\OmiseWebhooks\HandleCustomerDestroy::class,
        // 'customer_update_card' => \App\Jobs\OmiseWebhooks\HandleCustomerUpdateCard::class,

        // 'dispute_create' => \App\Jobs\OmiseWebhooks\HandleDisputeCreate::class,
        // 'dispute_update' => \App\Jobs\OmiseWebhooks\HandleDisputeUpdate::class,
        // 'dispute_destroy' => \App\Jobs\OmiseWebhooks\HandleDisputeDestroy::class,
        // 'dispute_activate' => \App\Jobs\OmiseWebhooks\HandleDisputeActivate::class,
        // 'dispute_deactivate' => \App\Jobs\OmiseWebhooks\HandleDisputeDeactivate::class,
        // 'dispute_verify' => \App\Jobs\OmiseWebhooks\HandleDisputeVerify::class,

        // 'refund_create' => \App\Jobs\OmiseWebhooks\HandleRefundCreate::class,

        // 'schedule_create' => \App\Jobs\OmiseWebhooks\HandleScheduleCreate::class,
        // 'schedule_destroy' => \App\Jobs\OmiseWebhooks\HandleScheduleDestroy::class,
        // 'schedule_expire' => \App\Jobs\OmiseWebhooks\HandleScheduleExpire::class,
        // 'schedule_expiring' => \App\Jobs\OmiseWebhooks\HandleScheduleExpiring::class,
        // 'schedule_suspend' => \App\Jobs\OmiseWebhooks\HandleScheduleSuspend::class,

        // 'transfer_create' => \App\Jobs\OmiseWebhooks\HandleTransferCreate::class,
        // 'transfer_update' => \App\Jobs\OmiseWebhooks\HandleTransferUpdate::class,
        // 'transfer_destroy' => \App\Jobs\OmiseWebhooks\HandleTransferDestroy::class,
        // 'transfer_fail' => \App\Jobs\OmiseWebhooks\HandleTransferFail::class,
        // 'transfer_pay' => \App\Jobs\OmiseWebhooks\HandleTransferPay::class,
        // 'transfer_send' => \App\Jobs\OmiseWebhooks\HandleTransferSend::class,
    ],

    /*
     * The classname of the model to be used. The class should equal or extend
     * Spatie\WebhookClient\Models\WebhookCall.
     */
    'model' => \Spatie\WebhookClient\Models\WebhookCall::class,

    /**
     * This class determines if the webhook call should be stored and processed.
     */
    'profile' => \Soap\OmiseWebhooks\OmiseWebhookProfile::class,

    /*
     * Specify a connection and or a queue to process the webhooks
     */
    'connection' => env('OMISE_WEBHOOK_CONNECTION'),
    'queue' => env('OMISE_WEBHOOK_QUEUE'),

    /*
     * When disabled, the package will not verify if the signature is valid.
     * This can be handy in local environments.
     */
    'verify_signature' => env('OMISE_SIGNATURE_VERIFICATION', true),
];
```
Next, you must publish the migration with:
```
php artisan vendor:publish --provider="Spatie\WebhookClient\WebhookClientServiceProvider" --tag="webhook-client-migrations"
```
After the migration has been published you can create the webhook_calls table by running the migrations:
```
php artisan migrate
```
Finally, take care of the routing: At the Omise dashboard you must configure at what url Omise webhooks should hit your app. In the routes file of your app you must pass that route to ``` Route::omiseWebhooks```:
```
Route::omiseWebhooks('webhook-route-configured-at-the-omise-dashboard');
```
Behind the scenes this will register a POST route to a controller provided by this package. Because Omise has no way of getting a csrf-token, you must add that route to the except array of the VerifyCsrfToken middleware:
```php
protected $except = [
    'webhook-route-configured-at-the-omise-dashboard',
];
```

## Usage
[Omise](https://www.omise.co/) will send out webhooks for serveral event types. You can find the full list of event types in Omise documentation.

However, Omise doesnot sign requests sending to our application. So, for simplicity we check only source IPs from Omise. Omise reccomends us to re-verify object status again.
You can customise it yourself using [Laravel-Omise](https://github.com/soap/laravel-omise) package.

Unless something goes terribly wrong, this package will always respond with a 200 to webhook requests. Sending a 200 will prevent Omise from resending the same event over and over again. Omise might occasionally send a duplicate webhook request more than once. This package makes sure that each request will only be processed once. All webhook requests with a valid source IP will be logged in the webhook_calls table. The table has a payload column where the entire payload of the incoming webhook is saved.

If the source IP is not valid, the request will not be logged in the webhook_calls table but a Spatie\StripeWebhooks\WebhookFailed exception will be thrown. If something goes wrong during the webhook request the thrown exception will be saved in the exception column. In that case the controller will send a 500 instead of 200.

There are two ways this package enables you to handle webhook requests: you can opt to queue a job or listen to the events the package will fire.
#### Handling webhook request using jobs
If you want to do something when a specific event type comes in you can define a job that does the work. Here's an example of such a job:
```php
```
We highly recommend that you make this job queueable, because this will minimize the response time of the webhook requests. This allows you to handle more stripe webhook requests and avoid timeouts.

After having created your job you must register it at the jobs array in the omise-webhooks.php config file. The key should be the name of the stripe event type where but with the . replaced by _. The value should be the fully qualified classname. In the configuration file, most of events are configured but commented out. So you can should the desired event and uncomment the line.
```php
```
In case you want to configure one job as default to process all undefined event, you may set the job at default_job in the omise-webhooks.php config file. The value should be the fully qualified classname.
```php
// config/stripe-webhooks.php
'default_job' => \App\Jobs\OmiseWebhooks\HandleOtherEvent::class,
```

#### Handling webhook request using events
Instead of queueing jobs to perform some work when a webhook request comes in, you can opt to listen to the events this package will fire. Whenever a valid request hits your app, the package will fire a omise-webhooks::<name-of-the-event> event.
The payload of the events will be the instance of WebhookCall that was created for the incoming request.

Let's take a look at how you can listen for such an event. In the EventServiceProvider you can register listeners
```php
/**
 * The event listener mappings for the application.
 *
 * @var array
 */
protected $listen = [
    'omise-webhooks::charge.expire' => [
        App\Listeners\ChargeExpire::class,
    ],
];
```
### Create your webhook endpoint.

### Register route.

### Exclude CSRF verification on the webhook route.

### Create jobs to handle webhook calls.
```php

```

## Testing

```bash
vendor\bin\pest
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Prasit Gebsaap](https://github.com/soap)
- [All Contributors](../../contributors)
- Spatie team for their [Laravel Stripe Webhook](https://github.com/spatie/laravel-stripe-webhooks) and Laravel Webhook package.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
