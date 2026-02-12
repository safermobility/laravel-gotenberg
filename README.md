# Create PDFs in Laravel apps using Gotenberg

## IMPORTANT!

**This package is no longer maintained, because [`spatie/laravel-pdf`](https://github.com/spatie/laravel-pdf) now has support for Gotenberg in 2.1+.**

---

## Overview

[![Latest Version on Packagist](https://img.shields.io/packagist/v/safermobility/laravel-gotenberg.svg?style=flat-square)](https://packagist.org/packages/safermobility/laravel-gotenberg)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/safermobility/laravel-gotenberg/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/safermobility/laravel-gotenberg/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/safermobility/laravel-gotenberg/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/safermobility/laravel-gotenberg/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/safermobility/laravel-gotenberg.svg?style=flat-square)](https://packagist.org/packages/safermobility/laravel-gotenberg)

This package provides a simple way to create PDFs in Laravel apps. Under the hood it uses [Gotenberg](https://gotenberg.dev) to generate PDFs from Blade views. You can use modern CSS features like grid and flexbox to create beautiful PDFs.

This package is very heavily based on Spatie's [laravel-pdf](https://github.com/spatie/laravel-pdf) package, but does not require Node.js to run, making
it more suitable for applications that run in containers.

## Installation

You can install the package via composer:

```bash
composer require safermobility/laravel-gotenberg
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="gotenberg-config"
```

This is the contents of the published config file:

```php
return [
    'host' => env('GOTENBERG_HOST'),
];
```

## Usage

You must have a working Gotenberg instance.

For development, you may be able to use the Gotenberg Demo server:

```env
GOTENBERG_HOST=https://demo.gotenberg.dev
```

For production, you must set up your own Gotenberg instance.

### Simple PDF generation

```php
use SaferMobility\LaravelGotenberg\Facades\Gotenberg;

Gotenberg::view('pdfs.invoice', ['invoice' => $invoice])
    ->format('letter')
    ->save('invoice.pdf')
```

This will render the Blade view `pdfs.invoice` with the given data and save it as a PDF file.

You can also return the PDF as a response from your controller:

```php
use SaferMobility\LaravelGotenberg\Facades\Gotenberg;

class DownloadInvoiceController
{
    public function __invoke(Invoice $invoice)
    {
        return Gotenberg::view('pdfs.invoice', ['invoice' => $invoice])
            ->format('letter')
            ->name('your-invoice.pdf');
    }
}
```

This will use a streamed response to reduce memory usage.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [SaferMobility](https://github.com/safermobility)
- [Spatie](https://github.com/spatie) - for their Laravel PDF package, and many others

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
