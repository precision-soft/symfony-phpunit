# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [v3.2.1] - 2026-04-13

### Changed

- `MockContainerTrait` — declare `abstract public static function getMockDto(): MockDto` to enforce the contract at compile time for consumers of the trait

### Fixed

- `MockContainer::getOrCreateMock()` — only return early when the mock instance exists; the previous `isset($this->mockDtos[...])` check caused `getMock()` to throw `MockNotFoundException` when a DTO was registered but the mock had not yet been created

## [v3.2.0] - 2026-04-12

### Changed

- `ManagerRegistryMock` — `getEntityManagerMock()`, `getClassMetadataMock()`, `getConnectionMock()` visibility widened from `private` to `protected`
- `MockContainer` — `getOrCreateMock()`, `createMock()` visibility widened from `private` to `protected`; `$mockDtos`, `$mocks`, `$creating` properties from `private` to `protected`
- `MockContainerTrait` — `$mockContainer` property and `initializeMockContainer()` visibility widened from `private` to `protected`
- `MockDto` — constructor-promoted properties `$class`, `$construct`, `$partial`, `$onCreate` widened from `private readonly` to `protected readonly`

## [v3.1.1] - 2026-04-10

### Fixed

- `MockContainer::getOrCreateMock()` — also check `$this->mockDtos` during mock lookup to prevent double instantiation when a DTO is registered but the mock has not yet been created
- `ManagerRegistryMock::getOnCreate()` — call `getEntityManagerMock()` once before `shouldReceive()` to avoid a redundant second call
- `ManagerRegistryMock::getOnCreate()` — use `static::` instead of `self::` for late static binding when calling `getEntityManagerMock()`
- `ManagerRegistryMock::getClassMetadataMock()` — drop unused `$innerMockContainer` parameter from the `ClassMetadata` closure
- `MockContainerTrait` — extract `initializeMockContainer()` to remove duplicated `??= new MockContainer()` from `registerMockDto()` and `registerMock()`

## [v3.1.0] - 2026-04-07

### Added

- `MockContainer::getOrRegisterMock()` — register and retrieve a mock in one call; returns the existing mock if already registered
- `ManagerRegistryMock::setManagedEntityClasses()` — restrict which entity classes are considered managed; `getManagerForClass()` returns `null` for classes outside the list
- `ManagerRegistryMock::resetManagedEntityClasses()` — clear the managed entity classes list
- `ManagerRegistryMockTrait` — opt-in PHPUnit trait with `#[After]` hook that resets `ManagerRegistryMock` static state automatically after each test

### Fixed

- `MockContainer::createMock()` — on `onCreate` failure, also remove the registered `MockDto` (previously only the mock instance was removed, leaving a stale DTO)
- `ManagerRegistryMock::getRepository()` — return the same mock for repeated calls with the same entity class (previously returned a new mock each time)
- `composer.json install-hooks` — add `${COMPOSER_DEV_MODE:-0}` default to prevent silent failure when the variable is unset

### Changed

- `ManagerRegistryMock::getManagerForClass()` — returns `null` for classes not in the managed list when `setManagedEntityClasses()` has been configured (previously always returned the entity manager regardless)

## [v3.0.1] - 2026-04-05

### Added

- `ManagerRegistryMock` — default `getRepository()` mock on EntityManager (returns `Mockery::mock(EntityRepository::class)`)
- `ManagerRegistryMock` — default `getManagerForClass()` mock on ManagerRegistry
- `MockContainer::getMock()` and `MockContainerTrait::get()` — `@template T` generic return type annotations (`MockInterface&T`)

### Fixed

- `MockContainerTrait::tearDown()` — null out `$mockContainer` after `close()` to allow garbage collection

### Changed

- `ManagerRegistryMock` — rename `$id` parameter to `$entityId` in `getReference` closure
- `ManagerRegistryMock` — add `\` prefix to `class_exists()` call
- `TestKernel` — add `\` prefix to `sys_get_temp_dir()` calls for consistency
- Move `symfony/string` from `suggest` to `require` — `SluggerInterfaceMock` depends on it at runtime
- `.dev/docker/entrypoint.sh` — skip `composer install` when `composer.lock` hash matches cached vendor
- Update `phpstan-baseline.neon`

## [v3.0.0] - 2026-04-04

### Breaking Changes

- Upgrade from PHPUnit 9 to PHPUnit 11.5 — consumers must update their `phpunit.xml.dist` to PHPUnit 11 format (`<source>` instead of `<coverage>`, `<extensions>` instead of `<listeners>`, `SYMFONY_PHPUNIT_VERSION` set to `11.5`)
- Upgrade from PHPStan 1.x to PHPStan 2.x (`phpstan/phpstan: ^2.0`, `phpstan/phpstan-mockery: ^2.0`)

### Changed

- Replace `<coverage processUncoveredFiles="true">` with `<source>` element in `phpunit.xml.dist`
- Replace `<listeners>` with `<extensions>` using `Symfony\Bridge\PhpUnit\SymfonyExtension`
- Add `@param class-string` PHPDoc to `MockContainer::getMock()`, `registerMock()`, `hasMock()` and `MockContainerTrait::get()`, `registerMock()`
- Extract `$onCreateClosure` variable in `MockContainer::createMock()` to avoid double `getOnCreate()` call
- Use `MockInterface&ConcreteClass` intersection types in test `@var` annotations for PHPStan 2 compatibility
- Replace `assertTrue(true)` with `addToAssertionCount(1)` in no-op test methods
- Remove unused `Mockery\MockInterface` import from `MockContainerTraitTestCase`

## [v2.1.3] - 2026-04-03

### Added

- Add `hasMock()` tests — covers false when nothing registered, true after `registerMockDto()`, true after `registerMock()`, false after `close()`
- Add deep nested chain test (3+ levels) — validates recursive dependency resolution across `DeepNestedServiceDto` → `FirstMockDto` → `SecondMockDto`
- Add nullable constructor parameter tests — validates optional parameters using defaults and mixed with mock dependencies
- Add `DeepNestedServiceDto` and `NullableConstructorDto` test utility classes
- Add `Extending AbstractTestCase` section to README — documents custom base test case pattern with shared helpers

## [v2.1.2] - 2026-04-01

### Added

- Add `hasMock(string $class): bool` method to `MockContainer` — checks if a mock or mock DTO is registered for a class
- Add `phpstan/phpstan-mockery` extension — resolves `byDefault()` false positives, reducing PHPStan baseline from 6 to 3 entries
- Add `export-ignore` rules to `.gitattributes` — excludes tests, dev infrastructure, and config from Composer installs
- Add `MockContainerTrait` standalone usage section to README
- Add PHP version, PHPStan level, code style, and license badges to README

### Changed

- Remove `final` from `MockContainer` and `MockDto` — allow library consumers to extend
- Replace `\Throwable` FQN with `use Throwable` import in `MockContainer`
- Guard pre-existing mock registration in `ManagerRegistryMock` — `getEntityManagerMock()`, `getClassMetadataMock()`, `getConnectionMock()` now skip registration when the mock already exists, preventing `MockAlreadyRegisteredException`

### Removed

- Remove stale `AUDIT_REPORT.md` (outdated v1.1.3 report)

## [v2.1.1] - 2026-03-30

### Changed

- Wrap `MockContainer::createMock()` in `try/finally` to clean circular dependency guard on exception
- Standardize `self::` over `static::` in `EventDispatcherInterfaceMock`, `SluggerInterfaceMock`, `ManagerRegistryMock`
- Add `class-string` PHPDoc to `MockDto` constructor and `getClass()`
- Extract concrete test doubles from anonymous classes in `MockContainerTraitTest`, reducing PHPStan baseline from 13 to 6 entries
- Add missing `use` import statements to all README code samples
- Fix `check_container()` fall-through logic in `utility.sh`

## [v2.1.0] - 2026-03-30

### Added

- Add circular dependency detection in `MockContainer::createMock()` — throws `CircularDependencyException` instead of infinite recursion
- Add `registerMock()` method to `MockContainerTrait` — allows registering pre-built `MockInterface` instances directly from test cases
- Add `MockeryPHPUnitIntegration` trait to all standalone test classes — ensures Mockery expectations are verified and state is cleaned up
- Add `failOnRisky` and `failOnWarning` attributes to `phpunit.xml.dist`

### Changed

- Tighten Composer version constraints from wildcard (`1.*`) to caret (`^1.0`) notation
- Fix `registerMock()` example in README — was accessing private `$this->mockContainer`, now uses public trait API
- Fix README dev section — replace non-existent `./dc` script with actual `docker compose` commands
- Add `CircularDependencyException` to README exceptions table
- Remove `privileged: true` from Docker dev container
- Regenerate PHPStan baseline for updated line numbers

## [v2.0.4] - 2026-03-30

### Changed

- Fix `close()` method description in README — was incorrectly stating it calls `Mockery::close()`
- Fix pre-commit hook: `php_cs_fixer()` now uses positional parameter instead of global variable, remove unused argument from `php_unit` call, and use `stop()` consistently for error handling
- Guard git hooks relinking in `dc` script to avoid redundant `rm -rf && ln -s` on every invocation

### Removed

- Remove orphaned `phpcs.xml` config file — no `squizlabs/php_codesniffer` dependency exists

## [v2.0.3] - 2026-03-29

### Changed

- Delegate Mockery lifecycle to `MockeryPHPUnitIntegration` trait instead of calling `Mockery::close()` directly in `MockContainer::close()`
- Add `vendor/bin/.phpunit/` to PHPStan `scanDirectories` for better type resolution

## [v2.0.2] - 2026-03-28

### Changed

- Improve `composer.json` description and normalize `phpstan/phpstan` version constraint
- Update PHPUnit XML schema from 9.3 to 9.6
- Rename `phpunit.xml` to `phpunit.xml.dist` and add `phpunit.xml` to `.gitignore`
- Use static closures in `EventDispatcherInterfaceMock`, `SluggerInterfaceMock`, `ManagerRegistryMock`, and test callbacks
- Rename test method to descriptive `testPartialMockWithConstructDependenciesResolvesCorrectly`
- Expand README: add runtime mock registration examples, exceptions table, and clarify `construct` parameter behavior
- Add missing `v1.2.1` section to CHANGELOG
- Rename `error()` to `print_error()` in pre-commit hook and remove unused `error()` function from `utility.sh`
- Remove `code_sniffer()` function from pre-commit hook
- Add `pstan()` function to `.dev/docker/.profile`

## [v2.0.1] - 2026-03-28

### Changed

- Use static closures in all mock definitions where `$this` is not used
- Expand PHPStan coverage and documentation

### Removed

- Remove unused dependency

## [v2.0.0] - 2026-03-27

### Breaking Changes

- Replace generic `Exception` with specific exception classes: `MockAlreadyRegisteredException`, `MockNotFoundException`, `MockContainerNotInitializedException`, `ClassNotFoundException` — code catching `PrecisionSoft\Symfony\Phpunit\Exception\Exception` must be updated
- Change `ManagerRegistryMock::getEntityManagerMock()`, `getClassMetadataMock()`, `getConnectionMock()` visibility from `public` to `private`
- Change `EntityManagerInterface` mock methods (`persist`, `flush`, `beginTransaction`, `commit`, `rollback`, `remove`, `clear`) from `andReturnSelf()` to `andReturnNull()` — matches real Doctrine behavior (these methods return `void`)
- Change `SluggerInterfaceMock::slug()` from returning `UnicodeString(\uniqid())` to returning `UnicodeString($inputString)` — deterministic results
- Change `EventDispatcherInterfaceMock::dispatch()` from `func_get_arg(0)` to typed `object $event` parameter
- Improve `MockDto::getConstruct()` return type from `MockDtoInterface[]|string[]|null` to `list<MockDto|MockDtoInterface|class-string<MockDtoInterface>|scalar>|null`

### Changed

- Rename `$mock` parameter to `$mockInterface` across all mock definitions
- Refactor `MockContainer::createMock()` from `switch` to explicit `if` chain with proper PHPStan type narrowing
- Rename project title from `symfony-phpunit` to `Symfony Phpunit`

### Added

- Add construct branch coverage tests

## [v1.2.1] - 2026-03-27

### Changed

- Improve mock container resolution with `getOrCreateMock()` for recursive dependency handling
- Harden `ManagerRegistryMock` mock definitions

### Added

- Add construct branch coverage tests

## [v1.2.0] - 2026-03-27

### Added

- Add PHPStan level 8 static analysis with baseline
- Add tests for mock behavior and edge cases
- Add `php-cs-fixer` PER-CS2.0 code style enforcement
- Add dev infrastructure (Docker, git hooks, utility scripts)

### Fixed

- Fix mock behavior inconsistencies

## [v1.1.3] - 2026-03-20

### Fixed

- Support required constructors in `ManagerRegistryMock::getReference()` by using `newInstanceWithoutConstructor()` when the constructor has required parameters

## [v1.1.2] - 2026-03-19

### Fixed

- Correct `MockDto` return types
- Add container safety checks

## [v1.1.1] - 2026-03-19

### Fixed

- Fix `composer.json` configuration
- Validate class existence in `ManagerRegistryMock` and remove duplicate `Mockery::mock()` call
- Move dev scripts to `.dev/` directory

## [v1.1.0] - 2026-03-18

### Changed

- Apply PHP code style rules: Yoda comparisons, no implicit boolean coercion

### Added

- Add unit tests for `ManagerRegistryMock` and `ClassMetadata`

### Fixed

- Fix `ManagerRegistryMock` `ClassMetadata` type
- Fix pre-commit hooks

## [v1.0.1] - 2025-10-25

### Changed

- Update packages
- Add `php-cs-fixer` configuration

## [v1.0.0] - 2024-09-17

### Added

- Initial release
- `MockDto` configuration pattern for declarative mock setup
- `MockContainer` for mock lifecycle management with lazy creation and dependency resolution
- `AbstractTestCase` and `AbstractKernelTestCase` base test classes
- `MockContainerTrait` for flexible test integration
- Built-in mocks: `ManagerRegistryMock`, `SluggerInterfaceMock`, `EventDispatcherInterfaceMock`

[v3.2.1]: https://github.com/precision-soft/symfony-phpunit/compare/v3.2.0...v3.2.1

[v3.2.0]: https://github.com/precision-soft/symfony-phpunit/compare/v3.1.1...v3.2.0

[v3.1.1]: https://github.com/precision-soft/symfony-phpunit/compare/v3.1.0...v3.1.1

[v3.1.0]: https://github.com/precision-soft/symfony-phpunit/compare/v3.0.1...v3.1.0

[v3.0.1]: https://github.com/precision-soft/symfony-phpunit/compare/v3.0.0...v3.0.1

[v3.0.0]: https://github.com/precision-soft/symfony-phpunit/compare/v2.1.3...v3.0.0

[v2.1.3]: https://github.com/precision-soft/symfony-phpunit/compare/v2.1.2...v2.1.3

[v2.1.2]: https://github.com/precision-soft/symfony-phpunit/compare/v2.1.1...v2.1.2

[v2.1.1]: https://github.com/precision-soft/symfony-phpunit/compare/v2.1.0...v2.1.1

[v2.1.0]: https://github.com/precision-soft/symfony-phpunit/compare/v2.0.4...v2.1.0

[v2.0.4]: https://github.com/precision-soft/symfony-phpunit/compare/v2.0.3...v2.0.4

[v2.0.3]: https://github.com/precision-soft/symfony-phpunit/compare/v2.0.2...v2.0.3

[v2.0.2]: https://github.com/precision-soft/symfony-phpunit/compare/v2.0.1...v2.0.2

[v2.0.1]: https://github.com/precision-soft/symfony-phpunit/compare/v2.0.0...v2.0.1

[v2.0.0]: https://github.com/precision-soft/symfony-phpunit/compare/v1.2.1...v2.0.0

[v1.2.1]: https://github.com/precision-soft/symfony-phpunit/compare/v1.2.0...v1.2.1

[v1.2.0]: https://github.com/precision-soft/symfony-phpunit/compare/v1.1.3...v1.2.0

[v1.1.3]: https://github.com/precision-soft/symfony-phpunit/compare/v1.1.2...v1.1.3

[v1.1.2]: https://github.com/precision-soft/symfony-phpunit/compare/v1.1.1...v1.1.2

[v1.1.1]: https://github.com/precision-soft/symfony-phpunit/compare/v1.1.0...v1.1.1

[v1.1.0]: https://github.com/precision-soft/symfony-phpunit/compare/v1.0.1...v1.1.0

[v1.0.1]: https://github.com/precision-soft/symfony-phpunit/compare/v1.0.0...v1.0.1

[v1.0.0]: https://github.com/precision-soft/symfony-phpunit/releases/tag/v1.0.0
