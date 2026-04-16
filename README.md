# Symfony Phpunit

[![PHP >= 8.2](https://img.shields.io/badge/php-%3E%3D8.2-8892BF)](https://www.php.net/)
[![PHPStan Level 8](https://img.shields.io/badge/phpstan-level%208-brightgreen)](https://phpstan.org/)
[![Code Style PER-CS2.0](https://img.shields.io/badge/code%20style-PER--CS2.0-blue)](https://www.php-fig.org/per/coding-style/)
[![License MIT](https://img.shields.io/badge/license-MIT-green)](LICENSE)

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
use Mockery\MockInterface;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\Mock\ManagerRegistryMock;
use PrecisionSoft\Symfony\Phpunit\MockDto;

new MockDto(
    class: CreateService::class,
    construct: [
        ManagerRegistryMock::class,
        new MockDto(FooRepository::class),
        'staticDependency',
    ],
    partial: true,
    onCreate: static function (MockInterface $mockInterface, MockContainer $mockContainer): void {
    },
);
```

**Parameters:**

| Parameter   | Type       | Default | Description                                                                             |
|-------------|------------|---------|-----------------------------------------------------------------------------------------|
| `class`     | `string`   | -       | FQCN of the class or interface to mock                                                  |
| `construct` | `?array`   | `null`  | Constructor arguments; `null` bypasses constructor, `[]` calls constructor with no args |
| `partial`   | `bool`     | `false` | If `true`, creates a partial mock via `makePartial()`                                   |
| `onCreate`  | `?Closure` | `null`  | Callback invoked after mock creation for setup                                          |

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

| Method                                             | Description                                              |
|----------------------------------------------------|----------------------------------------------------------|
| `registerMockDto(MockDto $mockDto): self`          | Register a mock configuration                            |
| `getMock(string $class): MockInterface`            | Get (or lazily create) a mock by class name              |
| `registerMock(string $class, MockInterface): self` | Register a pre-built mock directly                       |
| `close(): void`                                    | Clear all registered mock DTOs and cached mock instances |

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

### Kernel Test Case

Extend `AbstractKernelTestCase` for tests that need the Symfony kernel (e.g. testing services wired through the container):

```php
<?php

declare(strict_types=1);

namespace Acme\Test\Foo\Service;

use Acme\Foo\Repository\FooRepository;
use Acme\Foo\Service\CreateService;
use PrecisionSoft\Symfony\Phpunit\Mock\ManagerRegistryMock;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use PrecisionSoft\Symfony\Phpunit\TestCase\AbstractKernelTestCase;

final class CreateServiceKernelTest extends AbstractKernelTestCase
{
    public static function getMockDto(): MockDto
    {
        return new MockDto(
            CreateService::class,
            [
                ManagerRegistryMock::class,
                new MockDto(FooRepository::class),
            ],
        );
    }

    public function testCreate(): void
    {
        $createService = $this->get(CreateService::class);

        static::assertInstanceOf(CreateService::class, $createService);
    }
}
```

`AbstractKernelTestCase` extends Symfony's `KernelTestCase`, so `self::bootKernel()`, `self::getContainer()`, and all kernel test utilities are available alongside the mock container.

### Using MockContainerTrait Directly

If you need a custom base test case instead of extending `AbstractTestCase` or `AbstractKernelTestCase`, use `MockContainerTrait` directly. Your test class must implement `MockDtoInterface`:

```php
<?php

declare(strict_types=1);

namespace Acme\Test;

use PHPUnit\Framework\TestCase;
use PrecisionSoft\Symfony\Phpunit\Contract\MockDtoInterface;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use PrecisionSoft\Symfony\Phpunit\TestCase\Trait\MockContainerTrait;

abstract class CustomTestCase extends TestCase implements MockDtoInterface
{
    use MockContainerTrait;
}
```

The trait provides `setUp()` (registers the mock from `getMockDto()`), `tearDown()` (closes the container), `get()`, `registerMockDto()`, and `registerMock()`.

### Extending AbstractTestCase

When your project needs shared test helpers (e.g. factory methods, common assertions, or reusable setup logic), create your own base test case that extends `AbstractTestCase`:

```php
<?php

declare(strict_types=1);

namespace Acme\Test;

use PrecisionSoft\Symfony\Phpunit\TestCase\AbstractTestCase;

abstract class ProjectTestCase extends AbstractTestCase
{
    protected function createValidEntity(): Entity
    {
        $entity = new Entity();
        $entity->setName('default');

        return $entity;
    }
}
```

All concrete test classes then extend `ProjectTestCase` and implement `getMockDto()` as usual:

```php
<?php

declare(strict_types=1);

namespace Acme\Test\Foo\Service;

use Acme\Foo\Service\CreateService;
use Acme\Test\ProjectTestCase;
use PrecisionSoft\Symfony\Phpunit\Mock\ManagerRegistryMock;
use PrecisionSoft\Symfony\Phpunit\MockDto;

final class CreateServiceTest extends ProjectTestCase
{
    public static function getMockDto(): MockDto
    {
        return new MockDto(
            CreateService::class,
            [ManagerRegistryMock::class],
            true,
        );
    }

    public function testCreate(): void
    {
        $entity = $this->createValidEntity();
        $createService = $this->get(CreateService::class);

        /** test with shared helper */
    }
}
```

The same pattern applies to `AbstractKernelTestCase` — extend it when kernel access is needed alongside your shared helpers.

### Nested Dependencies

Constructor dependencies are resolved recursively. Each element in `construct` can be:

| Type                                      | Example                             | Resolution                  |
|-------------------------------------------|-------------------------------------|-----------------------------|
| `MockDto` instance                        | `new MockDto(FooRepository::class)` | Resolved into a mock        |
| `MockDtoInterface` instance               | `new FooRepositoryMock()`           | Resolved via `getMockDto()` |
| `class-string<MockDtoInterface>`          | `ManagerRegistryMock::class`        | Resolved via `getMockDto()` |
| Scalar (`string`, `int`, `float`, `bool`) | `'api-key-123'`, `42`               | Passed as-is                |

```php
use PrecisionSoft\Symfony\Phpunit\Mock\EventDispatcherInterfaceMock;
use PrecisionSoft\Symfony\Phpunit\Mock\ManagerRegistryMock;
use PrecisionSoft\Symfony\Phpunit\MockDto;

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
use Mockery\MockInterface;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\MockDto;

public static function getMockDto(): MockDto
{
    return new MockDto(
        CacheInterface::class,
        null,
        false,
        static function (MockInterface $mockInterface, MockContainer $mockContainer): void {
            $mockInterface->shouldReceive('get')
                ->byDefault()
                ->andReturnUsing(static function (string $key, callable $callback) {
                    return $callback();
                });
        },
    );
}
```

### Partial Mocks

Set the third parameter to `true` to create a partial mock. Real methods are called unless explicitly overridden:

```php
use PrecisionSoft\Symfony\Phpunit\Mock\ManagerRegistryMock;
use PrecisionSoft\Symfony\Phpunit\MockDto;

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

The library provides pre-configured mocks for common Symfony and Doctrine interfaces.
Each built-in mock requires additional packages — install them as needed:

| Mock                           | Requires                                                    |
|--------------------------------|-------------------------------------------------------------|
| `ManagerRegistryMock`          | `doctrine/orm`, `doctrine/doctrine-bundle`                  |
| `SluggerInterfaceMock`         | `symfony/string`                                            |
| `EventDispatcherInterfaceMock` | `symfony/event-dispatcher-contracts` (included via Symfony) |

- **`ManagerRegistryMock`** -- Mocks `ManagerRegistry` with a full `EntityManagerInterface` (persist, flush, commit, rollback, getReference, etc.), `ClassMetadata`, and `Connection`. To restrict which entity classes resolve to an entity manager via `getManagerForClass()`, use `configureManagedEntityClasses()` on the mock instance — configuration is per-mock with no static state:

```php
final class CreateServiceTest extends AbstractTestCase
{
    public static function getMockDto(): MockDto { ... }

    public function testCreate(): void
    {
        $registry = $this->getMock(ManagerRegistry::class);
        ManagerRegistryMock::configureManagedEntityClasses($registry, [Customer::class]);

        /** no reset needed — state lives on the mock, not the class */
    }
}
```

> The static `setManagedEntityClasses()` / `resetManagedEntityClasses()` helpers (and `ManagerRegistryMockTrait`'s `#[After]` hook) remain available for backward compatibility but are deprecated since 3.3.0 and will be removed in 4.0.0.

- **`EventDispatcherInterfaceMock`** -- Mocks `EventDispatcherInterface` with a `dispatch()` that returns the dispatched event.
- **`SluggerInterfaceMock`** -- Mocks `SluggerInterface` with a `slug()` that returns a `UnicodeString` containing the input string.

### Registering Additional Mocks at Runtime

Use `registerMockDto()` to register additional mock configurations during a test:

```php
use Mockery\MockInterface;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\MockDto;

public function testFoo(): void
{
    $this->registerMockDto(new MockDto(
        BarService::class,
        null,
        false,
        static function (MockInterface $mockInterface, MockContainer $mockContainer): void {
            $mockInterface->shouldReceive('process')
                ->once()
                ->andReturn(true);
        },
    ));

    $barService = $this->get(BarService::class);
}
```

Use `registerMock()` to register a pre-built `MockInterface` directly:

```php
use Mockery;

public function testFoo(): void
{
    $mockInterface = Mockery::mock(BarService::class);
    $mockInterface->shouldReceive('process')->once()->andReturn(true);

    $this->registerMock(BarService::class, $mockInterface);

    $barService = $this->get(BarService::class);
}
```

### Exceptions

All exceptions are in the `PrecisionSoft\Symfony\Phpunit\Exception` namespace:

| Exception                              | Thrown when                                                                                                                                                                         |
|----------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `CircularDependencyException`          | `MockContainer::createMock()` detects that a mock's constructor dependency graph contains a cycle (class A depends on B which depends back on A).                                   |
| `ClassNotFoundException`               | `MockContainer::getOrRegisterMock()` is called with a `MockDto` whose class string does not exist, or `EntityManagerInterface::getReference()` is called with a non-existent class. |
| `MockAlreadyRegisteredException`       | `MockContainer::registerMockDto()` or `MockContainer::registerMock()` is called for a class that already has a registered DTO or mock instance.                                     |
| `MockNotFoundException`                | `MockContainer::getMock()` is called for a class that has no registered `MockDto`.                                                                                                  |
| `MockContainerNotInitializedException` | A `MockContainerTrait` method (e.g. `get()`) is called before `setUp()` has initialised the container.                                                                              |

## Limitations

- **`ManagerRegistryMock`** — default stubs cover the methods declared on `ManagerRegistry` and the commonly-used subset of `EntityManagerInterface` (persist/remove/flush/find/contains/lock/wrapInTransaction/createQuery/etc.). `Query`, `QueryBuilder`, `NativeQuery`, `UnitOfWork`, `Configuration` return bare Mockery stubs — tests that need concrete behavior must set expectations on them explicitly.
- **`Connection`** is mocked as a non-partial mock; only `executeStatement` has a default expectation. Any other method must be stubbed per test.
- **`ClassMetadata`** — `getClassMetadata()` returns the **same** `ClassMetadata` mock for every entity class. Tests that need per-entity metadata must override the `getClassMetadata` expectation. The mock stubs only `setIdGeneratorType` and `setIdGenerator` (both as `null` returns). All other methods require explicit expectations.
- **`getReference()`** — creates an entity instance and sets its ID via a `setId()` method convention. Entities without a `setId()` method will not have their ID set. Entities with required constructor parameters are instantiated via `ReflectionClass::newInstanceWithoutConstructor()` — readonly constructor-promoted properties will be in an invalid state. This is acceptable for test references used only as identity markers.
- **`resetManager()`** — returns the existing `EntityManagerInterface` mock. Unlike real Doctrine, it does **not** close the current manager or create a new instance.
- **`SluggerInterfaceMock`** — `slug()` returns the raw input wrapped in `UnicodeString` without any slug transformation (no lowercasing, no dash-joining, no special character stripping). Tests that assert on slug format must override the expectation.
- **Repositories** — `EntityManagerInterface::getRepository($entityName)` returns a per-entity Mockery mock cached for the lifetime of the `MockContainer`. Expectations set on one retrieval are seen on subsequent retrievals of the same entity.
- **`MockDto::$partial`** — uses Mockery's `makePartial()`, which bypasses the real constructor. For classes with required constructor state that backs real fall-through methods, pass `construct` with valid arguments instead of relying on partial mode.
- **Parallel in-process execution** — the deprecated `ManagerRegistryMock::setManagedEntityClasses()` holds static state; use `configureManagedEntityClasses()` for per-mock scoping. All other state lives on `MockContainer` instances.

## Dev

The development environment uses Docker. The `./dc` script is a Docker Compose wrapper located in `.dev/`.

```shell
git clone git@github.com:precision-soft/symfony-phpunit.git
cd symfony-phpunit

./dc build && ./dc up -d
```
