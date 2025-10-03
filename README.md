# Zenigata Helpers

A lightweight collection of PHP helper classes to simplify common development tasks.
Currently includes utilities for **cache interaction**, **container resolution**, and **stub file generation**.

## Features

- Unified API for [PSR-6](https://www.php-fig.org/psr/psr-6/#interfaces) and [PSR-16](https://www.php-fig.org/psr/psr-16/#interfaces) interfaces.
- Simple service resolution with optional **type validation** from a [PSR-11](https://www.php-fig.org/psr/psr-11/#3-interfaces) container.
- Stub file generation with **token replacement** and safe directory handling.

## Requirements

- PHP >= 8.2
- [Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos)

## Installation

```bash
composer require zenigata/helpers
```

## Usage

### `CacheHelper`

Utility for interacting with both **PSR-6** and **PSR-16** caches with a unified API.

It hides the differences between the two standards, so you can use the **same methods** regardless of the underlying cache implementation.

```php
use Psr\Cache\CacheItemPoolInterface; // PSR-6
use Psr\SimpleCache\CacheInterface;   // PSR-16
use Zenigata\Helpers\CacheHelper;

// $cache implements CacheItemPoolInterface or CacheInterface

// With PSR-6
$item = $cache->getItem('foo');
$value = $item->isHit() ? $item->get() : null;

// With PSR-16
$value = $cache->get('foo', null);

// With CacheHelper: same code works for both
$value = CacheHelper::getItem($cache, 'foo');

// With PSR-6
$item = $cache->getItem('foo');
$item->set('bar')->expiresAfter(3600);
$cache->save($item);

// With PSR-16
$cache->set('foo', 'bar', 3600);

// With CacheHelper
CacheHelper::setItem($cache, 'foo', 'bar', 3600);
```

Supports single and multiple operations:

- `getItem`, `setItem`, `deleteItem`, `hasItem`
- `getItems`, `setItems`, `deleteItems`, `clear`

### `ContainerResolver`

Helper for resolving services from a **PSR-11 container**, with optional type enforcement.

This makes it easy to validate that a resolved service matches the expected class or interface, and ensures that you always get **consistent exceptions** when something is missing or of the wrong type.

```php
use Example\CustomLogger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Zenigata\Helpers\ContainerResolver;

// $container implements ContainerInterface

// Simple resolution without type check
$logger = ContainerResolver::resolve($container, 'logger');

// Object validation: throws InvalidArgumentException if the service is not a LoggerInterface
$logger = ContainerResolver::resolve(
    container:  $container,
    id:         'logger',
    instanceOf: LoggerInterface::class
);

// Object validation against multiple classes/interfaces
$service = ContainerResolver::resolve(
    container:  $container,
    id:         'logger',
    instanceOf: [CustomLogger::class, LoggerInterface::class]
);
```

If the service is missing or does not match the expected type(s), an `InvalidArgumentException` is thrown.

### `StubRenderer`

Utility for generating files from **stub templates** with placeholder replacement and automatic directory creation.

```php
use Zenigata\Helpers\StubRenderer;

/*
Stub file: stubs/Class.stub

<?php

namespace {{namespace}};

class {{class}}
{
    public function hello(): string
    {
        return "Hello World!";
    }
}
*/

StubRenderer::render(
    stub:         __DIR__ . '/stubs/Class.stub',
    destination:  __DIR__ . '/src/MyClass.php',
    placeholders: [
        '{{namespace}}' => 'Example',
        '{{class}}'     => 'MyClass',
    ]
);

/*
Resulting file: src/MyClass.php

<?php

namespace Example;

class MyClass
{
    public function hello(): string
    {
        return "Hello from MyClass!";
    }
}
*/
```

- Replaces placeholders (e.g. `{{namespace}}`, `{{class}}`) with given values.
- Ensures the destination directory exists.
- Throws a `RuntimeException` if the stub cannot be read or the file cannot be written.

## Contributing

Pull requests are welcome! For major changes, please open an issue first to discuss what you would like to change.

Keep the implementation minimal, focused, and well-documented, making sure to update tests accordingly.

See [CONTRIBUTING](./CONTRIBUTING.md) for more information.

## License

This library is licensed under the MIT license. See [LICENSE](./LICENSE) for more information.

