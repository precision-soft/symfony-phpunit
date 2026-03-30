# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
