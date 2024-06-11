# Laravel-Modules

[![Latest Version on Packagist](https://img.shields.io/packagist/v/Bitsnio/laravel-modules.svg?style=flat-square)](https://packagist.org/packages/Bitsnio/laravel-modules)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Scrutinizer Coverage](https://img.shields.io/scrutinizer/coverage/g/Bitsnio/laravel-modules.svg?maxAge=86400&style=flat-square)](https://scrutinizer-ci.com/g/Bitsnio/laravel-modules/?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/Bitsnio/laravel-modules.svg?style=flat-square)](https://packagist.org/packages/Bitsnio/laravel-modules)

| **Laravel** | **laravel-modules** |
|-------------|---------------------|
| 5.4         | ^1.0                |
| 5.5         | ^2.0                |
| 5.6         | ^3.0                |
| 5.7         | ^4.0                |
| 5.8         | ^5.0                |
| 6.0         | ^6.0                |
| 7.0         | ^7.0                |
| 8.0         | ^8.0                |
| 9.0         | ^9.0                |
| 10.0        | ^10.0               |
| 11.0        | ^11.0               |

`bitsnio/laravel-modules` is a Laravel package created to manage your large Laravel app using modules. A Module is like a Laravel package, it has some views, controllers or models. This package is supported and tested in Laravel 11.

This package is a re-published, re-organised and maintained version of [pingpong/modules](https://github.com/pingpong-labs/modules), which isn't maintained anymore.

With one big bonus that the original package didn't have: **tests**.

## upgrade
To upgrade to version V11 follow [Upgrade Guide](https://laravelmodules.com/docs/v11/upgrade) on official document.

## Install

To install via Composer, run:

``` bash
composer require bitsnio/laravel-modules
```

The package will automatically register a service provider and alias.

Optionally, publish the package's configuration file by running:

``` bash
php artisan vendor:publish --provider="Bitsnio\Modules\LaravelModulesServiceProvider"
```

### Autoloading

By default, the module classes are not loaded automatically. You can autoload your modules by adding merge-plugin to the extra section:

```json
"extra": {
    "laravel": {
        "dont-discover": []
    },
    "merge-plugin": {
        "include": [
            "Modules/*/composer.json"
        ]
    }
},
```

**Tip: don't forget to run `composer dump-autoload` afterwards.**

## Documentation

You'll find installation instructions and full documentation on [https://laravelmodules.com/](https://laravelmodules.com/docs).

## Demo

You can see a demo using Laravel Breeze at https://github.com/laravel-modules-com/breeze-demo

This is a complete application using Auth, Base and Profile modules.

## Community

We also have a Discord community. [https://discord.gg/hkF7BRvRZK](https://discord.gg/hkF7BRvRZK) For quick help, ask questions in the appropriate channel.

## Credits

- [Nicolas Widart](https://github.com/Bitsnio)
- [David Carr](https://github.com/dcblogdev)
- [gravitano](https://github.com/gravitano)
- [All Contributors](../../contributors)

## About Nicolas Widart

Nicolas Widart is a freelance web developer specialising on the Laravel framework. View all my packages [on my website](https://bitsnio.com/), or visit [my website](https://nicolaswidart.com).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
