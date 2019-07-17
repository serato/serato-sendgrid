# Serato SendGrid [![Build Status](https://img.shields.io/travis/serato/serato-sendgrid.svg)](https://travis-ci.org/serato/serato-sendgrid)

[![Latest Stable Version](https://img.shields.io/packagist/v/serato/serato-sendgrid.svg)](https://packagist.org/packages/serato/serato-sendgrid)

A PHP library for sending transactional emails using SendGrid

## Adding to a project via composer.json

To include this library in a PHP project add the following line to the project's
`composer.json` file in the `require` section:

```json
{
	"require": {
		"serato/serato-sendgrid": "dev-master"
	}
}
```
See [Packagist](https://packagist.org/packages/serato/serato-sendgrid) for a list of all 
available versions.

## Requirements

This library requires PHP 7.1 or greater.

## Style guide

Please ensure code adheres to the [PHP-FIG PSR-2 Coding Style Guide](http://www.php-fig.org/psr/psr-2/)

Use [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer/wiki) to validate your code against coding standards:

```bash
$ ./vendor/bin/phpcs
```

## PHPStan

Use PHPStan for static code analysis:

```bash
$ vendor/bin/phpstan analyse
```

## Unit tests

Configuration for PHPUnit is defined within [phpunit.xml](phpunit.xml).

To run tests:

```bash
$ php vendor/bin/phpunit
```