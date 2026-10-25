# Create webhooks for Omise payment gateway with ease

[![Latest Version on Packagist](https://img.shields.io/packagist/v/soap/laravel-omise-webhooks.svg?style=flat-square)](https://packagist.org/packages/soap/laravel-omise-webhooks)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/soap/laravel-omise-webhooks/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/soap/laravel-omise-webhooks/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/soap/laravel-omise-webhooks/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/soap/laravel-omise-webhooks/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/soap/laravel-omise-webhooks.svg?style=flat-square)](https://packagist.org/packages/soap/laravel-omise-webhooks)

[Omise](https://www.omise.co/) can notify your application of events using webhooks. This package can help you handle those webhooks. Out of the box it will verify the Omise signature of all incoming requests. All valid calls will be logged to the database. You can easily define jobs or events that should be dispatched when specific events hit your app.

This package will not handle what should be done after the webhook request has been validated and the right job or event is called. You should still code up any work (eg. regarding payments) yourself.

Before using this package we highly recommend reading [the entire documentation on webhooks over at Omise](https://docs.omise.co/api-webhooks).

This package is built on top of [spatie/laravel-webhook-client](https://github.com/spatie/laravel-webhook-client) and is heavily inspired by [spatie/laravel-stripe-webhooks](https://github.com/spatie/laravel-stripe-webhooks).

## Support us



## Versions

| Version | Branch | PHP | Laravel | Support |
|---------|--------|-----|---------|---------|
| 2.x | [`main`](https://github.com/soap/laravel-omise-webhooks/tree/main) | ^8.3 | 12 – 13 | Active |
| 1.x | [`v1.x`](https://github.com/soap/laravel-omise-webhooks/tree/v1.x) | ^8.3 | 10 – 13 | Security fixes until December 2027 |

Version 2 verifies signatures by default and supports resolving signing secrets per tenant. It requires Laravel 12 or 13; on Laravel 10 or 11, use version 1.x. If you are upgrading from 1.x, see [Upgrading from 1.x](#upgrading-from-1x).

## Installation

You can install the package via composer:

```bash
composer require soap/laravel-omise-webhooks:^2.0
```
The service provider will automatically register itself.

You must publish the config file with:

```bash
php artisan vendor:publish --tag="omise-webhooks-config"
```

This is the contents of the config file that will be published at `config/omise-webhooks.php`:

```php
use Soap\OmiseWebhooks\OmiseWebhooksProfile;
use Soap\OmiseWebhooks\ProcessOmiseWebhooksJob;
use Spatie\WebhookClient\Models\WebhookCall;

return [
    /*
     * The webhook secret (base64-encoded) shown in the Omise dashboard.
     * It is used to verify the `Omise-Signature` header of incoming webhooks.
     * See https://docs.omise.co/api-webhooks#webhook-signature-verification
     */
    'signing_secret' => env('OMISE_WEBHOOK_SECRET'),

    /*
     * You can define a default job that should be run for all other Omise event type
     * without a job defined in next configuration.
     * You may leave it empty to store the job in database but without processing it.
     */
    'default_job' => '',

    /*
     * You can define the job that should be run when a certain webhook hits your application
     * here. The key is the name of the Omise event type (called key for Omise) with the `.` replaced by a `_`.
     *
     * You can find a list of Omise webhook types here:
     * https://docs.opn.ooo/api-webhooks#supported-events.
     */
    'jobs' => [
        // 'card_destroy' => \App\Jobs\OmiseWebhooks\HandleCardDestroy::class,
        // 'card_update' => \App\Jobs\OmiseWebhooks\HandleCardUpdate::class,

        // 'charge_capture' => \App\Jobs\OmiseWebhooks\HandleChargeCapture::class,
        // 'charge_complete' => \App\Jobs\OmiseWebhooks\HandleChargeComplete::class,
        // 'charge_create' => \App\Jobs\OmiseWebhooks\HandleChargeCreate::class,
        // 'charge_expire' => \App\Jobs\OmiseWebhooks\HandleChargeExpire::class,
        // 'charge_reverse' => \App\Jobs\OmiseWebhooks\HandleChargeReverse::class,
        // 'charge_update' => \App\Jobs\OmiseWebhooks\HandleChargeUpdate::class,

        // ... customer_*, dispute_*, refund_*, schedule_* and transfer_* events
    ],

    /*
     * The classname of the model to be used. The class should equal or extend
     * Spatie\WebhookClient\Models\WebhookCall.
     */
    'model' => WebhookCall::class,

    /**
     * This class determines if the webhook call should be stored and processed.
     */
    'profile' => OmiseWebhooksProfile::class,

    /*
     * The job that fires the events and dispatches your jobs for a stored webhook call.
     * The class should equal or extend Soap\OmiseWebhooks\ProcessOmiseWebhooksJob.
     */
    'process_webhook_job' => ProcessOmiseWebhooksJob::class,

    /*
     * Specify a connection and or a queue to process the webhooks
     */
    'connection' => env('OMISE_WEBHOOK_CONNECTION'),
    'queue' => env('OMISE_WEBHOOK_QUEUE'),

    /*
     * Signatures are verified by default; requests without a valid signature, or for which
     * no signing secret is configured, are rejected with a 403 response.
     * Only disable this in local environments.
     */
    'verify_signature' => env('OMISE_SIGNATURE_VERIFICATION', true),

    /*
     * The maximum age (in seconds) of the `Omise-Signature-Timestamp` header,
     * used to protect against replay attacks. Set to 0 to disable this check.
     */
    'signature_tolerance' => env('OMISE_SIGNATURE_TOLERANCE', 300),

    /*
     * When `verify_ip` is enabled, this setting determines if the signature should be
     * validated using request 's IP address.
     */
    'verify_ip' => env('OMISE_IP_VERIFICATION', false),
];
```

In the `signing_secret` key of the config file you should add a valid webhook secret. You can find the secret in the [webhook settings of the Omise dashboard](https://dashboard.omise.co/v2/settings/webhooks).

> [!IMPORTANT]
> Signature verification is **enabled by default**, and requests are rejected when no signing secret is configured. Outside production, you may set `OMISE_SIGNATURE_VERIFICATION=false` until you have configured a secret. Never disable it in production.

Next, you must publish the migration with:
```bash
php artisan vendor:publish --provider="Spatie\WebhookClient\WebhookClientServiceProvider" --tag="webhook-client-migrations"
```
After the migration has been published you can create the `webhook_calls` table by running the migrations:
```bash
php artisan migrate
```

Finally, take care of the routing: at the Omise dashboard you must configure at what url Omise webhooks should hit your app. In the routes file of your app you must pass that route to `Route::omiseWebhooks`:
```php
// routes/web.php
Route::omiseWebhooks('omise/webhooks');
```
Behind the scenes this will register a `POST` route to a controller provided by this package. Because Omise has no way of getting a csrf-token, you must exclude that route from CSRF verification.

In applications with the Laravel 11+ structure, do this in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'omise/webhooks',
    ]);
})
```
In applications that still have the `VerifyCsrfToken` middleware (e.g. upgraded from Laravel 10), add the route to its `$except` array:
```php
protected $except = [
    'omise/webhooks',
];
```
Alternatively, register the route in `routes/api.php`, which does not use CSRF verification. In that case the full url will be prefixed with `/api` (e.g. `https://your-app.com/api/omise/webhooks`).

## Usage

Omise will send out webhooks for several event types. You can find the [full list of event types](https://docs.omise.co/api-webhooks#supported-events) in the Omise documentation.

Every webhook call is an Omise *event* object. The event type is found in the `key` field, and the object the event is about (a charge, refund, transfer, ...) is in the `data` field:

```json
{
  "object": "event",
  "id": "evnt_test_5xyz...",
  "livemode": false,
  "location": "/events/evnt_test_5xyz...",
  "key": "charge.complete",
  "created_at": "2026-10-25T08:00:00Z",
  "data": {
    "object": "charge",
    "id": "chrg_test_5xyz...",
    "amount": 100000,
    "currency": "THB",
    "status": "successful",
    "paid": true,
    "metadata": {
      "order_id": "1234"
    }
  }
}
```

### Webhook signature verification
Omise signs every webhook request with the `Omise-Signature` and `Omise-Signature-Timestamp` headers ([documentation](https://docs.omise.co/api-webhooks#webhook-signature-verification)). Verification is enabled by default, so copy the webhook secret from the Omise dashboard to your `.env`:
```
OMISE_WEBHOOK_SECRET=your-base64-webhook-secret
# Optional: maximum age of the signature timestamp in seconds (default 300, 0 disables the check)
OMISE_SIGNATURE_TOLERANCE=300
```
The package computes an HMAC-SHA256 of `<timestamp>.<raw body>` using the base64-decoded secret and compares it with the signature header in constant time. While a secret is being rolled, Omise sends two comma-separated signatures; a match against either one is accepted.

Requests with a missing or invalid signature are rejected with a `403` response, and so are all requests when no signing secret is configured. Rejected requests fire Spatie's `Spatie\WebhookClient\Events\InvalidWebhookSignatureEvent`, which you can listen to for logging.

#### Verifying a signature yourself
If you handle Omise webhooks in your own code instead of the package's route, you can use the validator directly. It does not read the package config and never skips verification:

```php
use Soap\OmiseWebhooks\OmiseSignatureValidator;

// From a request
OmiseSignatureValidator::verify($request, $base64Secret, tolerance: 300); // bool

// From raw headers and body
OmiseSignatureValidator::verifySignature($rawBody, $signatureHeader, $timestampHeader, $base64Secret, 300); // bool

// Throws Soap\OmiseWebhooks\Exceptions\InvalidOmiseSignature with the reason of the failure
OmiseSignatureValidator::assertValid($rawBody, $signatureHeader, $timestampHeader, $base64Secret, 300);
```

You can additionally restrict requests to Omise's webhook source IPs by setting `OMISE_IP_VERIFICATION=true`. The list of IPs is fetched from `https://cdn.omise.co/ips.json`.

Omise still recommends re-fetching the object to verify its status, which you can do with the [Laravel-Omise](https://github.com/soap/laravel-omise) package (see [the example below](#verifying-the-object-with-the-omise-api)).

### How requests are handled

Unless something goes terribly wrong, this package will always respond with a `200` to webhook requests. Sending a `200` will prevent Omise from resending the same event over and over again.

- **Valid requests** are stored in the `webhook_calls` table. The `payload` column holds the entire payload of the incoming webhook. A `ProcessOmiseWebhooksJob` is then dispatched (on the configured connection and queue) which fires an event and dispatches your job.
- **Duplicate requests**: Omise might occasionally send the same event more than once. `OmiseWebhooksProfile` skips any event whose `id` has already been stored, so each event is processed only once.
- **Invalid requests** (missing or wrong signature, expired timestamp, no signing secret configured or, when enabled, an unknown source IP) are not stored. The controller responds with a `403` and a `Spatie\WebhookClient\Events\InvalidWebhookSignatureEvent` event is fired.
- **Errors**: if something goes wrong while dispatching the processing job, the exception is saved in the `exception` column of the stored webhook call and the controller responds with a `500`.

There are two ways this package enables you to handle webhook requests: you can opt to queue a job or listen to the events the package will fire.

### Handling webhook requests using jobs
If you want to do something when a specific event type comes in you can define a job that does the work. Here's an example of such a job that marks an order as paid when a charge completes:

```php
<?php

namespace App\Jobs\OmiseWebhooks;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\WebhookClient\Models\WebhookCall;

class HandleChargeComplete implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public WebhookCall $webhookCall) {}

    public function handle(): void
    {
        // The Omise event is available in $this->webhookCall->payload
        $charge = $this->webhookCall->payload['data'];

        if ($charge['status'] !== 'successful') {
            return;
        }

        Order::where('id', $charge['metadata']['order_id'] ?? null)
            ->update([
                'status' => 'paid',
                'omise_charge_id' => $charge['id'],
            ]);
    }
}
```
We highly recommend that you make this job queueable, because this will minimize the response time of the webhook requests. This allows you to handle more Omise webhook requests and avoid timeouts.

After having created your job you must register it in the `jobs` array of the `omise-webhooks.php` config file. The key should be the name of the Omise event type with the `.` replaced by `_`. The value should be the fully qualified classname. Most of the events are already listed but commented out, so you can simply uncomment the line you need. Event types that are not listed can be added in the same way:

```php
// config/omise-webhooks.php

'jobs' => [
    'charge_complete' => \App\Jobs\OmiseWebhooks\HandleChargeComplete::class,
    'charge_expire' => \App\Jobs\OmiseWebhooks\HandleChargeExpire::class,
    'refund_create' => \App\Jobs\OmiseWebhooks\HandleRefundCreate::class,
],
```
In case you want to configure one job as the default to process all events without a job of their own, you may set it as `default_job` in the `omise-webhooks.php` config file. The value should be the fully qualified classname.
```php
// config/omise-webhooks.php

'default_job' => \App\Jobs\OmiseWebhooks\HandleOtherEvent::class,
```
When neither a specific job nor a default job is configured, the webhook call is only stored and the event is fired.

### Handling webhook requests using events
Instead of queueing jobs to perform some work when a webhook request comes in, you can opt to listen to the events this package will fire. Whenever a valid request hits your app, the package will fire an `omise-webhooks::<name-of-the-event>` event, where the name is the original Omise event key (with the dots), e.g. `omise-webhooks::charge.complete`.

The payload of the events will be the instance of `WebhookCall` that was created for the incoming request.

Register the listener in the `boot` method of your `AppServiceProvider`:
```php
use App\Listeners\ChargeCompleteListener;
use Illuminate\Support\Facades\Event;

public function boot(): void
{
    Event::listen('omise-webhooks::charge.complete', ChargeCompleteListener::class);
}
```
In applications that still have an `EventServiceProvider` (e.g. upgraded from Laravel 10), you can register listeners there instead:
```php
/**
 * The event listener mappings for the application.
 *
 * @var array
 */
protected $listen = [
    'omise-webhooks::charge.complete' => [
        App\Listeners\ChargeCompleteListener::class,
    ],
];
```
Here's an example of such a listener:
```php
<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\WebhookClient\Models\WebhookCall;

class ChargeCompleteListener implements ShouldQueue
{
    public function handle(WebhookCall $webhookCall): void
    {
        $charge = $webhookCall->payload['data'];

        // do your work here
    }
}
```
We highly recommend that you make the event listener queueable, as this will minimize the response time of the webhook requests. This allows you to handle more Omise webhook requests and avoid timeouts.

You can also use a wildcard to listen to every Omise event:
```php
Event::listen('omise-webhooks::*', function (string $eventName, array $data) {
    /** @var \Spatie\WebhookClient\Models\WebhookCall $webhookCall */
    $webhookCall = $data[0];

    logger()->info("Received Omise event {$webhookCall->payload['key']}");
});
```

The above examples are only one way to handle events in Laravel. To learn the other options, read [the Laravel documentation on handling events](https://laravel.com/docs/events).

### Verifying the object with the Omise API
Omise recommends not trusting the webhook payload alone, but re-fetching the object from the API before acting on it. With [soap/laravel-omise](https://github.com/soap/laravel-omise) (already required by this package) you can do this inside your job or listener:

```php
use Soap\LaravelOmise\Omise\Error;

public function handle(): void
{
    $chargeId = $this->webhookCall->payload['data']['id'];

    $charge = app('omise')->charge()->find($chargeId);

    if ($charge instanceof Error) {
        // the charge could not be retrieved, let the job be retried
        $this->release(60);

        return;
    }

    if ($charge->isPaid()) {
        // mark the order as paid
    }
}
```

## Advanced usage

### Retry handling a webhook
All incoming webhook requests are written to the database. This is incredibly valuable when something goes wrong while handling a webhook call. You can easily retry processing the webhook call, after you've investigated and fixed the cause of failure, like this:

```php
use Soap\OmiseWebhooks\ProcessOmiseWebhooksJob;
use Spatie\WebhookClient\Models\WebhookCall;

dispatch(new ProcessOmiseWebhooksJob(WebhookCall::find($id)));
```

### Performing custom logic
You can add some custom logic that should be executed before and/or after the scheduling of your job or event by using your own process job class. It should extend `Soap\OmiseWebhooks\ProcessOmiseWebhooksJob`:

```php
<?php

namespace App\Jobs\OmiseWebhooks;

use Soap\OmiseWebhooks\ProcessOmiseWebhooksJob;

class MyCustomOmiseWebhookJob extends ProcessOmiseWebhooksJob
{
    public function handle()
    {
        // do some custom stuff beforehand

        parent::handle();

        // do some custom stuff afterwards
    }
}
```
Then register it in the `process_webhook_job` key of the config file:
```php
// config/omise-webhooks.php

'process_webhook_job' => \App\Jobs\OmiseWebhooks\MyCustomOmiseWebhookJob::class,
```

### Determining which webhooks should be processed
The `profile` key of the config file determines whether an incoming webhook call should be stored and processed. By default `OmiseWebhooksProfile` is used, which skips events that have already been stored. You can use your own profile by implementing `Spatie\WebhookClient\WebhookProfile\WebhookProfile`. For example, to ignore test-mode events in production:

```php
<?php

namespace App\Webhooks;

use Illuminate\Http\Request;
use Soap\OmiseWebhooks\OmiseWebhooksProfile;

class LiveOnlyOmiseWebhookProfile extends OmiseWebhooksProfile
{
    public function shouldProcess(Request $request): bool
    {
        if (app()->isProduction() && ! $request->boolean('livemode')) {
            return false;
        }

        return parent::shouldProcess($request);
    }
}
```
```php
// config/omise-webhooks.php

'profile' => \App\Webhooks\LiveOnlyOmiseWebhookProfile::class,
```

### Handling multiple signing secrets
When you receive webhooks from multiple Omise accounts, you can register the route with a `{configKey}` parameter:

```php
Route::omiseWebhooks('omise/webhooks/{configKey}');
```

Then add a signing secret for each key to the config file, using `signing_secret_{configKey}` as the key:

```php
// config/omise-webhooks.php

'signing_secret_account1' => env('OMISE_WEBHOOK_SECRET_ACCOUNT1'),
'signing_secret_account2' => env('OMISE_WEBHOOK_SECRET_ACCOUNT2'),
```

Finally, set the webhook url of each account in the Omise dashboard to e.g. `https://your-app.com/omise/webhooks/account1`.

### Resolving signing secrets at runtime (multi-tenancy)
When the secrets are stored per tenant, e.g. encrypted in the database, register a resolver in the `boot` method of a service provider. It receives the request and the `{configKey}` route parameter, and must return the base64-encoded secret as shown in the Omise dashboard. When a resolver is registered, the `signing_secret` config values are no longer used.

```php
use Illuminate\Http\Request;
use Soap\OmiseWebhooks\OmiseWebhooks;

OmiseWebhooks::resolveSigningSecretUsing(function (Request $request, ?string $configKey) {
    return PaymentAccount::where('account_key', $configKey)->first()?->webhook_secret; // decrypted by an encrypted cast
});
```

Returning `null` or an empty string rejects the request with a `403` response.

### Using a custom model
If you want to add extra behaviour to the stored webhook calls, you can use your own model. It should extend `Spatie\WebhookClient\Models\WebhookCall`:

```php
// config/omise-webhooks.php

'model' => \App\Models\OmiseWebhookCall::class,
```

### Testing webhooks locally
To receive webhooks on your local machine, expose your app with a tunnel such as [ngrok](https://ngrok.com/) or [Expose](https://expose.dev/) and set the public url as the webhook endpoint in the Omise test dashboard. You can set `OMISE_SIGNATURE_VERIFICATION=false` locally if you do not want to configure the secret.

You can also send a fake webhook to your app in a feature test:

```php
use Illuminate\Support\Facades\Bus;
use Soap\OmiseWebhooks\ProcessOmiseWebhooksJob;

it('stores an omise webhook call', function () {
    Bus::fake();

    $this->postJson('omise/webhooks', [
        'object' => 'event',
        'id' => 'evnt_test_123',
        'key' => 'charge.complete',
        'data' => ['object' => 'charge', 'id' => 'chrg_test_123'],
    ])->assertSuccessful();

    $this->assertDatabaseHas('webhook_calls', ['name' => 'omise']);

    Bus::assertDispatched(ProcessOmiseWebhooksJob::class);
});
```

## Upgrading from 1.x

Version 2 contains the following breaking changes:

- **Laravel 12 or 13 is required.** Support for Laravel 10 and 11 was dropped in 2.1.0 because Composer no longer installs them due to unpatched security advisories. Stay on 1.x until you upgrade Laravel.
- **Signature verification is enabled by default.** `OMISE_SIGNATURE_VERIFICATION` now defaults to `true`. Make sure `OMISE_WEBHOOK_SECRET` (and any `signing_secret_{configKey}` values) are set before upgrading, or explicitly set `OMISE_SIGNATURE_VERIFICATION=false` in local environments. If you published the config file, update the default of `verify_signature` there as well.
- **Requests without a signing secret are rejected.** Previously a missing secret threw `Spatie\WebhookClient\Exceptions\InvalidConfig`; now the request is treated as invalid.
- **Rejected requests return `403` instead of `500`.** `InvalidWebhookSignatureEvent` is still fired, so listen to it if you relied on the exception being reported.
- **`OmiseSignatureValidator::hasValidSignature()` was removed.** If you extended the validator, override `isValid()` or use the static [`verify()`, `verifySignature()` or `assertValid()`](#verifying-a-signature-yourself) methods instead.

New in version 2:

- [`OmiseWebhooks::resolveSigningSecretUsing()`](#resolving-signing-secrets-at-runtime-multi-tenancy) to resolve the signing secret per request, e.g. per tenant.
- [Config-independent signature verification](#verifying-a-signature-yourself) for webhooks handled outside the package's route.

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
