# Symfony Phpunit — example

A slice of a product catalogue — a `Category`, a `Product`, and the two services that manage them — whose test suite shows every public capability of `precision-soft/symfony-phpunit` on code that does something real. It is the minimum of code that demonstrates the maximum of the library: the services need exactly the collaborators the library ships doubles for (a Doctrine `ManagerRegistry`, a `SluggerInterface`, an `EventDispatcherInterface`), so every test file below is a scenario a consumer of the library will recognise.

Paths in this file are relative to `.example/`.

## What it represents

- `src/Entity/Category.php`, `src/Entity/Product.php` — the nomenclator entities; `setId()` exists so the mocked `getReference()` can populate an identifier.
- `src/Service/CategoryService.php` — resolves a category reference through the manager registry and refuses an entity Doctrine ORM does not manage.
- `src/Service/ProductCatalogService.php` — creates, renames, describes, removes and imports products: slugging, persisting, flushing, dispatching an event, a repository lookup, class metadata, a raw statement on the connection and a transaction wrapper.
- `src/Event/ProductCreatedEvent.php`, `src/Exception/` — the event the catalogue dispatches and the project's own exceptions.

## What each test shows

| Test file                                       | Library capability demonstrated                                                                                                                                                                                                                                                                                                                                                                                              |
|-------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Service/ProductCatalogServiceTest.php`   | `AbstractTestCase` with a partial `MockDto` for the service under test; the three `construct` forms (a built-in mock by `class-string`, a nested `MockDto`, the shared sub-mock both resolve to); `configureRepositoryFactory()` and `configureClassMetadataFactory()`; the connection and transaction doubles; `onCreate` with `byDefault()`; `withMock()` for a service built inside the scope, and the restore afterwards |
| `tests/Service/CategoryServiceTest.php`         | `getReference()` populating the identifier through `setId()`; `configureManagedEntityClasses()` making `getManagerForClass()` return `null` for an unmanaged class                                                                                                                                                                                                                                                           |
| `tests/Container/MockRegistrationTest.php`      | `registerMockDto()` and `registerMock()` at runtime; `MockNotFoundException`, `MockClassMismatchException`, `MockAlreadyRegisteredException` and `CircularDependencyException` on the misuse that raises each                                                                                                                                                                                                                |
| `tests/Functional/ProductCatalogKernelTest.php` | `AbstractKernelTestCase` booting a real micro-kernel (`tests/Utility/ProductCatalogKernel.php`, selected through `KERNEL_CLASS`): the `ManagerRegistry` mock set into a synthetic service, the real slugger and event dispatcher from the container, the kernel shut down by `tearDown()`                                                                                                                                    |
| `tests/Event/ProductCreatedEventTest.php`       | `MockeryPHPUnitIntegration` on its own, for a test that needs a Mockery double but no container                                                                                                                                                                                                                                                                                                                              |

Two behaviours worth knowing before writing a scenario of your own: creation is lazy, so a built-in mock's sub-mocks (`EntityManagerInterface`, `Connection`) are reachable through `$this->get()` only after the mock that registers them has been resolved; and `withMock()` restores what the container held before the scope — a class that was not registered before it is not registered after it either.

## How to run

The example installs the library from the working tree through a path repository, so it always tests the code as it stands. Its `composer.lock` is not committed: a fresh install resolves the dependencies at that moment, and the root's `composer.lock` stays the reproducible set.

```shell
cd .example
composer install
composer check    # phpstan, then the suite
```

From the repository root the same runs as one section of the gate, inside the dev container:

```shell
.dev/validate/all.sh --example
```

Code style is governed by the root's `.php-cs-fixer.dist.php`, which includes this directory, so `composer cs-check` at the root covers the example as well. The directory is `export-ignore`d and never reaches a consumer's `vendor/`.
