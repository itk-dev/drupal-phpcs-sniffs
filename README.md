# ITK Dev Drupal coding standards

Additional Drupal coding standards sniffs.

## Sniffs

| Sniff                              | Description                                                            |
|------------------------------------|------------------------------------------------------------------------|
| `ItkDevDrupal.Semantics.MethodLog` | Check that first argument to the `log` method[^1] is a constant string |

[^1]: And its convenient helper friends from
    [LoggerTrait](https://github.com/php-fig/log/blob/master/src/LoggerTrait.php) as well.

## Installation

``` shell
composer require --dev itk-dev/drupal-phpcs-sniffs
```

## Use

Include the `ItkDevDrupal` rule in your ruleset, e.g.

``` xml
<!-- phpcs.xml.dist -->
<ruleset name="My project rules">
 …
 <rule ref="ItkDevDrupal"/>
</ruleset>
```

or use the `ItkDevDrupal` standard specifically:

``` shell
php vendor/bin/phpcs --standard=ItkDevDrupal
```
