# symfony-phpunit

A Mockery-based testing library for Symfony applications that simplifies mock creation, dependency injection, and test setup through a declarative `MockDto` configuration pattern.

**You may fork and modify it as you wish**.

Any suggestions are welcomed.

## Requirements

- PHP >= 8.2
- Mockery 1.*
- Symfony PHPUnit Bridge 7.*

## Installation

```shell
composer require --dev precision-soft/symfony-phpunit
```

## Core Concepts

### MockDto

`MockDto` is the central configuration object that describes how a mock should be created.

```php
new MockDto(
    class: CreateService::class,
    construct: [
        ManagerRegistryMock::class,
        new MockDto(FooRepository::class),
        'staticDependency',
    ],
    partial: true,
    onCreate: function (MockInterface $mock, MockContainer $mockContainer): void {
    },
);
```

**Parameters:**

| Parameter   | Type       | Default | Description                                             |
|-------------|------------|---------|---------------------------------------------------------|
| `class`     | `string`   | -       | FQCN of the class or interface to mock                  |
| `construct` | `?array`   | `null`  | Constructor arguments; `null` means no constructor args |
| `partial`   | `bool`     | `false` | If `true`, creates a partial mock via `makePartial()`   |
| `onCreate`  | `?Closure` | `null`  | Callback invoked after mock creation for setup          |

### MockDtoInterface

Any class that implements `MockDtoInterface` must provide a static `getMockDto()` method. This allows classes (including test cases and reusable mock definitions) to declare their mock configuration.

```php
<?php

declare(strict_types=1);

use PrecisionSoft\Symfony\Phpunit\Contract\MockDtoInterface;
use PrecisionSoft\Symfony\Phpunit\MockDto;

class FooRepositoryMock implements MockDtoInterface
{
    public static function getMockDto(): MockDto
    {
        return new MockDto(FooRepository::class);
    }
}
```

### MockContainer

`MockContainer` is the registry that manages mock lifecycle. It lazily creates mocks from registered `MockDto` instances and resolves nested dependencies automatically.

**Methods:**

| Method                                             | Description                                 |
|----------------------------------------------------|---------------------------------------------|
| `registerMockDto(MockDto $mockDto): self`          | Register a mock configuration               |
| `getMock(string $class): MockInterface`            | Get (or lazily create) a mock by class name |
| `registerMock(string $class, MockInterface): self` | Register a pre-built mock directly          |
| `close(): void`                                    | Clear all mocks and call `Mockery::close()` |

## Usage

### Basic Test Case

Extend `AbstractTestCase` (or `AbstractKernelTestCase` for tests that need the Symfony kernel) and implement `getMockDto()`:

```php
<?php

declare(strict_types=1);

namespace Acme\Test\Foo\Service;

use Acme\Foo\Repository\FooRepository;
use Acme\Foo\Service\CreateService;
use PrecisionSoft\Symfony\Phpunit\Mock\ManagerRegistryMock;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use PrecisionSoft\Symfony\Phpunit\TestCase\AbstractTestCase;

final class CreateServiceTest extends AbstractTestCase
{
    public static function getMockDto(): MockDto
    {
        return new MockDto(
            CreateService::class,
            [
                ManagerRegistryMock::class,
                new MockDto(FooRepository::class),
                'staticDependency',
            ],
            true,
        );
    }

    public function testCreate(): void
    {
        $service = $this->get(CreateService::class);
    }
}
```

### Nested Dependencies

Constructor dependencies are resolved recursively. You can nest `MockDto` instances, reference `MockDtoInterface` class names, or pass scalar values:

```php
public static function getMockDto(): MockDto
{
    return new MockDto(
        OrderService::class,
        [
            new MockDto(
                PaymentGateway::class,
                [
                    new MockDto(HttpClientInterface::class),
                    'api-key-123',
                ],
            ),
            ManagerRegistryMock::class,
            EventDispatcherInterfaceMock::class,
        ],
    );
}
```

### onCreate Callbacks

Use the `onCreate` parameter to configure mock behavior after creation:

```php
public static function getMockDto(): MockDto
{
    return new MockDto(
        CacheInterface::class,
        null,
        false,
        function (MockInterface $mock, MockContainer $mockContainer): void {
            $mock->shouldReceive('get')
                ->byDefault()
                ->andReturnUsing(function (string $key, callable $callback) {
                    return $callback();
                });
        },
    );
}
```

### Partial Mocks

Set the third parameter to `true` to create a partial mock. Real methods are called unless explicitly overridden:

```php
public static function getMockDto(): MockDto
{
    return new MockDto(
        CreateService::class,
        [
            ManagerRegistryMock::class,
        ],
        true,
    );
}
```

### Built-in Mocks

The library provides pre-configured mocks for common Symfony and Doctrine interfaces:

- **`ManagerRegistryMock`** -- Mocks `ManagerRegistry` with a full `EntityManagerInterface` (persist, flush, commit, rollback, getReference, etc.), `ClassMetadata`, and `Connection`.
- **`EventDispatcherInterfaceMock`** -- Mocks `EventDispatcherInterface` with a `dispatch()` that returns the dispatched event.
- **`SluggerInterfaceMock`** -- Mocks `SluggerInterface` with a `slug()` that returns a unique `UnicodeString` per call.

## Dev

```shell
git clone git@github.com:precision-soft/symfony-phpunit.git
cd symfony-phpunit

./dc build && ./dc up -d
```
