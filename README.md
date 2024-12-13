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

You can publish the config file with:

```bash
php artisan vendor:publish --tag="omise-webhooks-config"
```

This is the contents of the published config file:

```php
return [
];
```


## Usage
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

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
