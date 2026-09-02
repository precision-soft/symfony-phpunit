# Symfony Phpunit

[![ci](https://github.com/precision-soft/symfony-phpunit/actions/workflows/ci.yml/badge.svg)](https://github.com/precision-soft/symfony-phpunit/actions/workflows/ci.yml)
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
- Symfony PHPUnit Bridge 7.* or 8.*

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

| Method                                                   | Description                                                             |
|----------------------------------------------------------|-------------------------------------------------------------------------|
| `registerMockDto(MockDto $mockDto): self`                | Register a mock configuration                                           |
| `getMock(string $class): MockInterface`                  | Get (or lazily create) a mock by class name                             |
| `registerMock(string $class, MockInterface): self`       | Register a pre-built mock directly                                      |
| `withMock(string $class, MockInterface, Closure): mixed` | Install a scoped override, restore the previous registration afterwards |
| `close(): void`                                          | Clear all registered mock DTOs and cached mock instances                |

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

The trait provides `setUp()` (registers the mock from `getMockDto()`), `tearDown()` (closes the container), `get()`, `registerMockDto()`, `registerMock()`, and `withMock()`.

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

The library provides pre-configured mocks for common Symfony and Doctrine interfaces. Each built-in mock requires additional packages — install them as needed:

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
        $managerRegistry = $this->get(ManagerRegistry::class);
        ManagerRegistryMock::configureManagedEntityClasses($managerRegistry, [Customer::class]);

        /** no reset needed — state lives on the mock, not the class */
    }
}
```

> The static `setManagedEntityClasses()` / `resetManagedEntityClasses()` helpers (and `ManagerRegistryMockTrait`'s `#[After]` hook) remain available for backward compatibility but are deprecated since 3.3.0 and will be removed in 4.0.0.

When repository or metadata behavior differs per entity, configure a factory on the entity manager mock. Both factories run lazily and cache one double per entity class, so repeated calls for the same entity return the same instance:

```php
$entityManager = $this->get(ManagerRegistry::class)->getManager();

ManagerRegistryMock::configureRepositoryFactory(
    $entityManager,
    static function (string $entityClass): MockInterface {
        $repositoryMockInterface = Mockery::mock(EntityRepository::class);
        $repositoryMockInterface->shouldReceive('findAll')
            ->andReturn(Customer::class === $entityClass ? [new Customer()] : []);

        return $repositoryMockInterface;
    },
);
```

`configureClassMetadataFactory()` works the same way for `getClassMetadata()`. A factory that returns anything other than a Mockery double of `EntityRepository` (respectively `ClassMetadata`) is rejected with a `MockClassMismatchException` naming the entity class.

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

### Overriding a Mock for Part of a Test

`withMock()` installs a mock for the duration of a callback and restores whatever was registered before — a mock instance, an unmaterialised `MockDto`, or nothing at all. The restore runs in a `finally`, so it also happens when the callback throws:

```php
use Mockery;

public function testFallbackPath(): void
{
    $failingMockInterface = Mockery::mock(BarService::class);
    $failingMockInterface->shouldReceive('process')->andThrow(new BarServiceUnavailableException());

    $fallbackResult = $this->withMock(
        BarService::class,
        $failingMockInterface,
        fn(): string => $this->get(FooService::class)->run(),
    );

    static::assertSame('fallback', $fallbackResult);

    /** the original BarService mock is back in the container here */
}
```

The callback receives the override and the container, and `withMock()` returns whatever the callback returns. A mock that is not an instance of `$class` is rejected with `MockClassMismatchException`, exactly as in `registerMock()`.

### Exceptions

All exceptions are in the `PrecisionSoft\Symfony\Phpunit\Exception` namespace:

| Exception                              | Thrown when                                                                                                                                                                                                                                                                                                                                                                                                                   |
|----------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `CircularDependencyException`          | `MockContainer::createMock()` detects that a mock's constructor dependency graph contains a cycle (class A depends on B which depends back on A).                                                                                                                                                                                                                                                                             |
| `ClassNotFoundException`               | `MockContainer::getOrRegisterMock()` is called with a `MockDto` whose class string does not exist, or `EntityManagerInterface::getReference()` is called with a non-existent class.                                                                                                                                                                                                                                           |
| `MockAlreadyRegisteredException`       | `MockContainer::registerMockDto()` or `MockContainer::registerMock()` is called for a class that already has a registered DTO or mock instance.                                                                                                                                                                                                                                                                               |
| `MockClassMismatchException`           | `MockContainer::registerMock()` or `MockContainer::withMock()` is called with a mock that is not an instance of the class it is registered under, which would make `getMock()` break its `MockInterface&T` contract. Also thrown by `ManagerRegistryMock::configureRepositoryFactory()` and `configureClassMetadataFactory()` when the supplied factory returns something that is not a Mockery double of the expected class. |
| `MockNotFoundException`                | `MockContainer::getMock()` is called for a class that has no registered `MockDto`.                                                                                                                                                                                                                                                                                                                                            |
| `MockContainerNotInitializedException` | A `MockContainerTrait` method (e.g. `get()`) is called before `setUp()` has initialised the container.                                                                                                                                                                                                                                                                                                                        |

## Test Conventions

These are the conventions every suite built on this package follows. They are recorded here because this package is the shared root of all of them; a suite that drifts from this list is the exception and should say why.

- `tests/` mirrors `src/` — one test file per source file, same relative path.
- Test classes are `final` and carry `@internal`.
- Assertions go through `static::assert*()`, never `$this->assert*()`.
- No `@test` annotations — method names start with `test`.
- Shared fixtures and helpers live in `tests/Utility/`, never in a base class. The only test-case base classes are
  `AbstractTestCase` and `AbstractKernelTestCase` from this package.
- Integration tests live in `tests/Functional/`, carry `#[Group('integration')]`, and are excluded from
  `composer test` so the default gate stays fast and offline. Run them with `composer test-integration`.

Two directories in this package's own suite deliberately mirror nothing in `src/`:

- `tests/Vendor/` — pins the Mockery behaviour the package is built on, so a dependency upgrade that changes those semantics fails here instead of silently weakening every consumer suite.
- `tests/Functional/` — drives a real PHPUnit process over a generated test case.

## Limitations

- **`ManagerRegistryMock`** — default stubs cover the methods declared on `ManagerRegistry` and the commonly-used subset of `EntityManagerInterface` (persist/remove/flush/find/contains/lock/wrapInTransaction/createQuery/etc.). `Query`, `QueryBuilder`, `NativeQuery`, `UnitOfWork`, `Configuration` return bare Mockery stubs — tests that need concrete behavior must set expectations on them explicitly.
- **`Connection`** is mocked as a non-partial mock; only `executeStatement` has a default expectation. Any other method must be stubbed per test.
- **`ClassMetadata`** — `getClassMetadata()` returns the **same** `ClassMetadata` mock for every entity class. Tests that need per-entity metadata must override the `getClassMetadata` expectation. The mock stubs only `setIdGeneratorType` and `setIdGenerator` (both as `null` returns). All other methods require explicit expectations.
- **`getReference()`** — creates an entity instance and sets its ID via a `setId()` method convention. Entities without a `setId()` method will not have their ID set. Entities with required constructor parameters are instantiated via `ReflectionClass::newInstanceWithoutConstructor()` — readonly constructor-promoted properties will be in an invalid state. This is acceptable for test references used only as identity markers.
- **`resetManager()`** — returns the existing `EntityManagerInterface` mock. Unlike real Doctrine, it does **not** close the current manager or create a new instance.
- **`SluggerInterfaceMock`** — `slug()` returns the raw input wrapped in `UnicodeString` without any slug transformation (no lowercasing, no dash-joining, no special character stripping). Tests that assert on slug format must override the expectation.
- **Repositories** — `EntityManagerInterface::getRepository($entityName)` returns a per-entity Mockery mock cached for the lifetime of the `MockContainer`. Expectations set on one retrieval are seen on subsequent retrievals of the same entity.
- **`MockDto::$partial`** — uses Mockery's `makePartial()`. The real constructor runs whenever `construct` is given, in partial and non-partial mode alike; it is only bypassed when `construct` is `null`, in which case the instance is created without constructor state. For classes whose real fall-through methods depend on constructor state, always pass `construct` with valid arguments.
- **Parallel in-process execution** — the deprecated `ManagerRegistryMock::setManagedEntityClasses()` holds static state; use `configureManagedEntityClasses()` for per-mock scoping. All other state lives on `MockContainer` instances.
- **`final` and `readonly` classes cannot be doubled through the container.** Mockery refuses `Mockery::mock(FinalThing::class)` outright, and the only double it can build for such a class is a proxied partial made from an instance (`Mockery::mock(new FinalThing())`) — which is not an instance of the class it proxies. `registerMock()` rejects it with `MockClassMismatchException`, because accepting it would make `getMock()` return something that is not the `MockInterface&T` it declares. Depend on an interface, or use the proxied partial directly instead of through the container.

## Exception context

Every exception in this package carries a structured `context` array next to its message, so the facts describing a failure do not have to be parsed back out of a string:

```php
try {
    /** ... */
} catch (Exception $exception) {
    $logger->error($exception->getMessage(), $exception->getContext());
}
```

`getContext()` returns `[]` when nothing was attached. `setContext()` replaces it and returns the exception, and the constructor accepts it as an optional fourth argument. Values are expected to be scalars, so the array stays serialisable by a logger.

The context is purely **additive**: no message, code or previous throwable changed when it was introduced, so code that logs only `getMessage()` behaves exactly as before.

One exception in this package populates it: `MockClassMismatchException` carries `expectedClass` and `actualClass`, because its message can only name the class the mock was registered under and the useful half of that failure is the class the mock actually belongs to. Everywhere else `MockContainer` rethrows the throwables it catches unchanged, so the capability is mainly there for consumers extending these exceptions in their own test infrastructure.

Every exception in the package implements `Contract\ExceptionInterface`, so a consumer can read the context off any of them without knowing the concrete class. A subclass of your own that already declares a `$context` property or a
`getContext()`/`setContext()` method will collide with `Exception\Trait\ExceptionTrait`.

## Example application

A runnable product catalogue slice lives under [`.example/`](./.example/README.md): the smallest code that needs exactly the collaborators this package ships doubles for, and a test suite that shows every public capability on it — `AbstractTestCase`, `MockDto` in its three `construct` forms, the built-in mocks and their factories, `withMock()`, runtime registration and each exception, and `AbstractKernelTestCase` against a real micro-kernel. It installs the package from the working tree through a path repository, so it always tests the code as it stands; run it with `.dev/validate/all.sh --example` or `cd .example && composer install && composer check`. The directory is `export-ignore`d and never reaches a consumer's `vendor/`.

## Dev

The development environment uses Docker. The `./dc` script is a Docker Compose wrapper located in `.dev/`.

```shell
git clone git@github.com:precision-soft/symfony-phpunit.git
cd symfony-phpunit

./dc build && ./dc up -d
```

Run the full gate the way the pre-commit hook runs it - the CI workflow in
`.github/workflows/ci.yml` calls the same composer scripts, so the two cannot drift:

```shell
.dev/validate/all.sh
.dev/validate/all.sh --audit        # also audits the locked dependencies ( needs the network )
.dev/validate/all.sh --example      # also installs and checks the example application under .example/
.dev/validate/all.sh --integration  # also runs the integration group, which composer test excludes
.dev/validate/all.sh --staged       # what the pre-commit hook runs: only the sections the index touches
```

Mutation testing is opt-in for the same reason, plus cost - it runs the suite once per mutant:

```shell
.dev/validate/all.sh --mutation
```

Infection is a pinned phar in the image, not a composer dependency, and `infection.json5` carries a
`minMsi` floor equal to the last measured score, so the section fails when a change makes the suite weaker rather than only reporting a number. Raise the floor when the score improves.

Build against another PHP version with the `PHP_VERSION` build argument - each version is tagged as its own image, so switching back and forth costs nothing:

```shell
PHP_VERSION=8.4 ./dc build && PHP_VERSION=8.4 ./dc up -d
```

Coverage is available through pcov, which is installed but disabled by default:

```shell
./dc exec dev php -d pcov.enabled=1 vendor/bin/simple-phpunit --coverage-text
```

After editing a file, `./dc restart dev` (a few seconds) is enough to be sure the container is not serving a stale copy - the bind mount can keep the old inode after an atomic rewrite.

pcov is built in the image from a pinned tarball rather than through `pecl install`, so a rebuild cannot move the coverage driver under the mutation baseline. `.dev/docker/.profile` keeps its bash-only helpers behind a `BASH_VERSION` guard, because `ENV` makes every POSIX `sh` session source the file too and busybox ash cannot parse `.dev/utility.sh`. The audit script is named `deps-audit` rather than `audit`: a Composer script named after one of Composer's own commands is skipped in silence.

The CI workflow splits its jobs along what actually varies per interpreter. `composer validate` and
`cs-check` read the same bytes on every PHP version, so they run once; `phpstan` and `test` run on each version of the matrix, because PHPStan's inference follows the interpreter. The integration group runs with
`--fail-on-skipped`, since it skips only when the real PHPUnit binary is missing - which after
`composer install` means a broken install rather than a legitimate skip.
