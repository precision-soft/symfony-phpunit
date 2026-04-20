# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [v3.4.2] - 2026-04-20 - Align late static binding on ManagerRegistryMock managed-entity writes

### Fixed

- `ManagerRegistryMock::setManagedEntityClasses()` and `resetManagedEntityClasses()` — writes to the managed-entity allow-list now go through `static::$managedEntityClasses` instead of `self::$managedEntityClasses`, matching the `static::` reads in `getOnCreate()` (lines 115/119). Subclasses that redeclare `$managedEntityClasses` previously had their override honored on read but silently bypassed on write, so `setManagedEntityClasses()` populated the parent property while `getManagerForClass()` checked the subclass property. This is an internal LSB alignment on an already-deprecated surface scheduled for removal in 4.0.0

## [v3.4.1] - 2026-04-20 - Normalize CHANGELOG format and align late static binding across built-in mocks

### Changed

- `CHANGELOG.md` — every release heading now uses the Keep-a-Changelog titled form (`## [vX.Y.Z] - YYYY-MM-DD - <Title>`) required by the `git-audit` changelog level; dated-only headings emitted an audit warning. Section ordering was normalized to `Breaking Changes` → `Fixed` → `Changed` → `Added` → `Deprecated` → `Removed` across all 24 entries, and a duplicate bullet was removed from the v1.0.0 entry
- `CHANGELOG.md` — thin, single-line entries (v1.0.1, v1.1.2, v2.0.1) were enriched with the concrete changes pulled from the tag diff; v3.3.0 items that described behavior changes (`self::` → `static::`, per-mock scoping) were moved from `Added` to `Changed` so section semantics match the actual change type; v3.4.0 gained the previously missing `composer audit` suppression entry that was only visible in the commit history
- `src/Mock/EventDispatcherInterfaceMock.php`, `src/Mock/SluggerInterfaceMock.php` — replace `self::getOnCreate()` with `static::getOnCreate()` inside `getMockDto()` so subclasses that override `getOnCreate()` are honored; aligns with the same late-static-binding fix applied to `ManagerRegistryMock` in v3.3.1
- `src/Mock/ManagerRegistryMock.php` — import `stdClass` via `use` instead of referencing it inline as `\stdClass::class`, matching the global "no inline FQN" rule applied elsewhere in the file
- `tests/bootstrap.php` — import `PrecisionSoft\Symfony\Phpunit\Test\Utility\TestKernel` via `use` instead of inline FQN inside the shutdown function

## [v3.4.0] - 2026-04-20 - Decompose EntityManagerInterface mock into focused helpers

### Changed

- `ManagerRegistryMock::getEntityManagerMock()` — the monolithic configure closure was split into nine focused `protected static` helpers: `configureTransactionApi`, `configurePersistenceApi`, `configureLifecycleApi`, `configureReferenceApi`, `configureMetadataApi`, `configureRepositoryApi`, `configureConnectionApi`, `configureQueryApi`, `configureWrapInTransactionApi`. Each helper covers one surface of `EntityManagerInterface` so subclasses can override a single area (e.g. to redefine the query API) without copy-pasting the whole closure
- `ManagerRegistryMock` — removed the inline `$repositoryMocks = []` closure capture; repository mocks are now owned by `configureRepositoryApi()` directly
- `phpstan-baseline.neon` — trimmed after the refactor exposed real types that previously needed ignored entries
- `tests/Mock/ManagerRegistryMockTest.php` — tests that previously fetched the EntityManagerInterface mock via `$registry->getManager()` now pull it directly from `MockContainer::getMock(EntityManagerInterface::class)`; both paths resolve to the same mock, and going through the container makes the dependency on the shared mock explicit in each test
- `composer.json` — add `config.audit.ignore` entries for `PKSA-5jz8-6tcw-pbk4` (argument injection via newline in PHP INI forwarded to child processes — dev-only, no untrusted INI input in our pipeline) and `PKSA-z3gr-8qht-p93v` (unsafe deserialization in PHPT coverage, fixed in PHPUnit 11.5.50+, pinned above the fix); each entry carries an inline rationale so `composer audit` stays green without hiding future advisories

### Added

- `ManagerRegistryMock::getQueryMock()`, `getQueryBuilderMock()`, `getNativeQueryMock()`, `getUnitOfWorkMock()`, `getConfigurationMock()` — five new `protected static` sub-mock factories extracted from the monolithic closure. Other test classes that need a query / query-builder / unit-of-work / configuration mock by itself can now call these helpers directly instead of reaching through `getEntityManagerMock()`

### Removed

- `ManagerRegistryMock` — unused `Doctrine\ORM\Query\ResultSetMapping` import

## [v3.3.1] - 2026-04-16 - Throw on duplicate DTO registration and expand EntityManager coverage

### Fixed

- `MockContainer::registerMockDto()` — now throws `MockAlreadyRegisteredException` when a mock instance is already registered for the same class (previously a ghost `MockDto` was silently created alongside the existing mock)
- `ManagerRegistryMock::getOnCreate()` — inside the `getManagerForClass` closure, `self::$managedEntityClasses` replaced with `static::$managedEntityClasses` so subclasses that override the property are honored
- `MockContainer::createMock()` catch block — drop the redundant `unset($this->mockDtos[...])` (the registration path already clears the DTO on success, and the catch body reruns cleanup)

### Changed

- `SluggerInterfaceMock::getOnCreate()` and `EventDispatcherInterfaceMock::getOnCreate()` — drop the unused `MockContainer` parameter from the `onCreate` closure signature
- `ManagerRegistryMock::getConnectionMock()` — drop the unused `MockContainer` parameter
- `MockContainerTrait::setUp()` — rewrite the `@phpstan-ignore` annotation using PHPStan 2.x syntax (identifier + explanatory reason) so `phpstan analyse --no-progress` no longer warns about malformed ignore comments
- `tests/Mock/ManagerRegistryMockTest.php` — replace deprecated `setManagedEntityClasses()` tests with `configureManagedEntityClasses()`; void-method smoke tests now use `shouldHaveReceived()->once()` for meaningful assertions instead of trivial calls
- README — Limitations section expanded with `getReference()` `setId()` convention, `ClassMetadata` single-instance behavior, `resetManager()` contract divergence, and `SluggerInterfaceMock` no-transformation behavior

### Added

- `ManagerRegistryMock` — `getConnection()` (from `ConnectionRegistry`) now stubbed on the registry mock, returning the managed `Connection` mock (consistency with the `EntityManager::getConnection()` stub)
- `tests/Mock/ManagerRegistryMockTest.php` — coverage for previously untested `EntityManagerInterface` methods: `detach()`, `refresh()`, `close()`, `lock()`, `createNativeQuery()`
- `tests/Container/MockContainerEdgeCaseTest.php` — coverage for `registerMockDto()` throwing when a mock is already registered
- `src/TestCase/Trait/ManagerRegistryMockTrait.php` — apply the `@deprecated since 3.3.0` annotation at code level so IDEs and static analyzers surface the warning on use

## [v3.3.0] - 2026-04-16 - Add configureManagedEntityClasses and harden ManagerRegistryMock coverage

### Fixed

- `ManagerRegistryMock::getReference()` return type widened from `object` to `?object` to match `EntityManagerInterface::getReference(): ?object` — previously tests could not exercise the null-handling path
- `SluggerInterfaceMock::slug()` return type widened from `UnicodeString` to `AbstractUnicodeString` to match `SluggerInterface::slug(): AbstractUnicodeString`
- `EventDispatcherInterfaceMock::dispatch()` default closure now accepts the optional `?string $eventName` parameter to match `EventDispatcherInterface::dispatch(object $event, ?string $eventName = null)`
- `ManagerRegistryMock::getClassMetadataMock()` — now registers the `ClassMetadata` mock through the container via `getOrRegisterMock()`, consistent with `getConnectionMock()` and the rest of the mock architecture
- `MockDto` — correct PHPDoc for the `$construct` constructor parameter (was documented as `$constructorArguments`)

### Changed

- `ManagerRegistryMock` — internal factory calls (`getOnCreate`, `getEntityManagerMock`, `getClassMetadataMock`, `getConnectionMock`) now use `static::` instead of `self::` so subclasses can override these extension points
- `MockContainer::registerMock()` — remove any previously registered `MockDto` for the same class during registration, so the container never holds both a live mock and a stale DTO for the same class
- `MockDto::__construct()` — validate `$class` against `class_exists` / `interface_exists` and throw `ClassNotFoundException` eagerly, surfacing typos at DTO construction instead of deferring to `getMock()`
- `MockContainer::getOrRegisterMock()` — drop the redundant `class_exists` / `interface_exists` check (now guaranteed by `MockDto::__construct()`)
- `MockContainer::getOrCreateMock()` — skip the extra `getMock()` indirection when the mock is already cached
- `MockContainer` — add PHPDoc blocks to `registerMockDto()`, `getMock()`, `getOrRegisterMock()`, `getOrCreateMock()`
- `MockContainerTrait` — no longer declares `abstract static function getMockDto()`; the constraint is now only on `MockDtoInterface` (still enforced by `AbstractTestCase` / `AbstractKernelTestCase`). Consumers using the trait directly may now skip the single-DTO contract; `setUp()` auto-registers only when `getMockDto()` is defined on the concrete class
- `ClassMetadata` mock — `setIdGeneratorType` and `setIdGenerator` defaults changed from `andReturnSelf()` to `andReturnNull()` to match Doctrine's `void` return type (previous default allowed invalid chaining)
- `ClassMetadata` mock — pass a valid `$name` constructor argument (`stdClass::class`) instead of bypassing the required parameter via `newInstanceWithoutConstructor`
- `Connection` mock — switch from partial (`partial=true`) to full (`partial=false`) mock; real `Connection` internals are never exercised and all stubbed methods behave the same
- `tests/Utility/TestKernel.php` — cache / log directories scoped per-process (`/tmp/symfony-phpunit-test-<pid>/…`) and cleaned up via `register_shutdown_function`, preventing cross-process collisions and dangling temp files
- `phpunit.xml.dist` — enable `executionOrder="random"`, `resolveDependencies="true"`, and `beStrictAboutOutputDuringTests="true"` so order-dependent tests and accidental output are caught in CI

### Added

- `ManagerRegistryMock::configureManagedEntityClasses(MockInterface $registryMock, array $entityClasses)` — per-mock alternative to `setManagedEntityClasses()` that holds no static state, safe under parallel in-process execution
- `ManagerRegistryMock` default stubs for previously unmocked `ManagerRegistry` methods: `getDefaultManagerName()`, `getManagers()`, `getManagerNames()`, `resetManager()`, `getDefaultConnectionName()`, `getConnections()`, `getConnectionNames()`
- `ManagerRegistryMock` default stubs for previously unmocked `EntityManagerInterface` methods: `find()`, `detach()`, `refresh()`, `contains()`, `close()`, `isOpen()`, `lock()`, `wrapInTransaction()`, `createQuery()`, `createQueryBuilder()`, `createNativeQuery()`, `getUnitOfWork()`, `getConfiguration()`. Query/QueryBuilder/NativeQuery/UnitOfWork/Configuration defaults return Mockery stubs that tests can override

### Deprecated

- `ManagerRegistryMock::setManagedEntityClasses()` / `resetManagedEntityClasses()` and `ManagerRegistryMockTrait` — use `configureManagedEntityClasses()` instead; will be removed in 4.0.0

## [v3.2.1] - 2026-04-13 - Enforce getMockDto contract and tighten mock lookup

### Fixed

- `MockContainer::getOrCreateMock()` — only return early when the mock instance exists; the previous `isset($this->mockDtos[...])` check caused `getMock()` to throw `MockNotFoundException` when a DTO was registered but the mock had not yet been created

### Changed

- `MockContainerTrait` — declare `abstract public static function getMockDto(): MockDto` so the contract is enforced at compile time for trait consumers, not only at runtime via `setUp()`

## [v3.2.0] - 2026-04-13 - Widen MockContainer, ManagerRegistryMock, and MockDto visibility to protected

### Changed

- `ManagerRegistryMock` — `getEntityManagerMock()`, `getClassMetadataMock()`, `getConnectionMock()` visibility widened from `private` to `protected` so subclasses can override individual mock factories
- `MockContainer` — `getOrCreateMock()`, `createMock()` visibility widened from `private` to `protected`; `$mockDtos`, `$mocks`, `$creating` properties widened from `private` to `protected`
- `MockContainerTrait` — `$mockContainer` property and `initializeMockContainer()` widened from `private` to `protected`
- `MockDto` — constructor-promoted properties `$class`, `$construct`, `$partial`, `$onCreate` widened from `private readonly` to `protected readonly`

## [v3.1.1] - 2026-04-10 - Fix MockContainer double-instantiation and late static binding

### Fixed

- `MockContainer::getOrCreateMock()` — also check `$this->mockDtos` during mock lookup so a registered DTO is reused instead of triggering a second instantiation when the mock has not yet been created
- `ManagerRegistryMock::getOnCreate()` — call `getEntityManagerMock()` once before `shouldReceive()` instead of calling it twice on the same setup path
- `ManagerRegistryMock::getOnCreate()` — use `static::` instead of `self::` when calling `getEntityManagerMock()` so subclass overrides are respected
- `ManagerRegistryMock::getClassMetadataMock()` — drop the unused `$innerMockContainer` parameter from the `ClassMetadata` closure
- `MockContainerTrait` — extract `initializeMockContainer()` so the duplicated `??= new MockContainer()` block is no longer inlined in both `registerMockDto()` and `registerMock()`

## [v3.1.0] - 2026-04-07 - Add getOrRegisterMock(), managed entity class list, and reset trait

### Fixed

- `MockContainer::createMock()` — on `onCreate` failure, remove both the mock instance and the registered `MockDto` (previously a stale DTO survived, producing phantom registrations on the next lookup)
- `ManagerRegistryMock::getRepository()` — return the same mock for repeated calls with the same entity class (previously returned a fresh mock each call, so expectations set on the first repository mock were invisible to subsequent lookups)
- `composer.json` `install-hooks` script — add `${COMPOSER_DEV_MODE:-0}` default so the command no longer silently fails when the variable is unset by the composer runtime

### Changed

- `ManagerRegistryMock::getManagerForClass()` — returns `null` for classes not in the managed list once `setManagedEntityClasses()` has been configured (previously always returned the entity manager regardless)

### Added

- `MockContainer::getOrRegisterMock()` — register and retrieve a mock in one call; returns the existing mock if already registered, otherwise builds and stores one
- `ManagerRegistryMock::setManagedEntityClasses()` / `resetManagedEntityClasses()` — opt-in allow-list for entity classes; `getManagerForClass()` returns `null` for classes outside the list
- `ManagerRegistryMockTrait` — opt-in PHPUnit trait with `#[After]` hook that resets `ManagerRegistryMock` static state automatically after each test
- Rename test utility `ThirdMockDtoInterface` → `ThirdMockDto` (moved to concrete `MockDto` type to match the registered-mock contract)

## [v3.0.1] - 2026-04-06 - Null out MockContainerTrait after close and align naming

### Fixed

- `MockContainerTrait::tearDown()` — null out `$mockContainer` after `close()` so the container and all captured mocks can be garbage-collected before the next test

### Changed

- `ManagerRegistryMock::getReference()` closure — rename `$id` parameter to `$entityId` for clarity
- `ManagerRegistryMock` — add `\` prefix to the `class_exists()` call (root-namespace consistency)
- `tests/Utility/TestKernel.php` — add `\` prefix to `sys_get_temp_dir()` calls for consistency with the rest of the root-namespace style
- Move `symfony/string` from `suggest` to `require` — `SluggerInterfaceMock` depends on it at runtime and installing without it broke autoload
- `.dev/docker/entrypoint.sh` — skip `composer install` when the `composer.lock` hash matches the cached vendor, cutting container start-up on warm reuse
- Update `phpstan-baseline.neon` against the refactored signatures

### Added

- `ManagerRegistryMock` — default `getRepository()` mock on the EntityManager mock (returns `Mockery::mock(EntityRepository::class)`)
- `ManagerRegistryMock` — default `getManagerForClass()` mock on the ManagerRegistry mock
- `MockContainer::getMock()` and `MockContainerTrait::get()` — `@template T` generic return annotations (`MockInterface&T`) so IDE/PHPStan pick up the concrete class

## [v3.0.0] - 2026-04-04 - Upgrade to PHPUnit 11.5 and PHPStan 2.x

### Breaking Changes

- Upgrade from PHPUnit 9 to PHPUnit 11.5 — consumers must update their `phpunit.xml.dist` to PHPUnit 11 format (`<source>` instead of `<coverage>`, `<extensions>` instead of `<listeners>`, `SYMFONY_PHPUNIT_VERSION` set to `11.5`)
- Upgrade from PHPStan 1.x to PHPStan 2.x (`phpstan/phpstan: ^2.0`, `phpstan/phpstan-mockery: ^2.0`) — consumers on PHPStan 1.x must bump and refresh their own baselines

### Changed

- `phpunit.xml.dist` — replace `<coverage processUncoveredFiles="true">` with `<source>` element; replace `<listeners>` with `<extensions>` using `Symfony\Bridge\PhpUnit\SymfonyExtension`
- `MockContainer::getMock()`, `registerMock()`, `hasMock()` and `MockContainerTrait::get()`, `registerMock()` — add `@param class-string` PHPDoc so PHPStan 2 narrows the class argument
- `MockContainer::createMock()` — extract `$onCreateClosure` local to avoid a double `getOnCreate()` call on the same DTO
- Test `@var` annotations — use `MockInterface&ConcreteClass` intersection types for PHPStan 2 compatibility
- No-op test bodies — replace `assertTrue(true)` with `addToAssertionCount(1)` so PHPUnit 11 risky-test detection stays quiet
- `tests/Utility/MockContainerTraitTestCase.php` — remove unused `Mockery\MockInterface` import
- Dev infrastructure (`.dev/docker/.profile`, `Dockerfile`, `entrypoint.sh`, `utility.sh`, `git-hooks/pre-commit`) — standardize shell helpers and hook wiring across the container

## [v2.1.3] - 2026-04-03 - Expand edge-case test coverage and document AbstractTestCase extension

### Added

- `tests/Container/MockContainerTest.php` / `MockContainerEdgeCaseTest.php` — `hasMock()` coverage (false when nothing registered, true after `registerMockDto()`, true after `registerMock()`, false after `close()`)
- Deep nested chain test (3+ levels) validating recursive dependency resolution across `DeepNestedServiceDto` → `FirstMockDto` → `SecondMockDto`
- Nullable constructor parameter tests — optional parameters used with defaults and mixed with mock dependencies
- New test utility fixtures: `tests/Utility/DeepNestedServiceDto.php`, `tests/Utility/NullableConstructorDto.php`
- README — `Extending AbstractTestCase` section documenting the custom base test case pattern with shared helpers

## [v2.1.2] - 2026-04-01 - Add hasMock(), phpstan-mockery extension, and export-ignore rules

### Changed

- `MockContainer` and `MockDto` — drop `final` so library consumers can extend for project-specific needs
- `MockContainer` — replace `\Throwable` FQN with `use Throwable` import
- `ManagerRegistryMock::getEntityManagerMock()`, `getClassMetadataMock()`, `getConnectionMock()` — guard against pre-existing registration so calling them after a mock is already registered no longer throws `MockAlreadyRegisteredException`

### Added

- `MockContainer::hasMock(string $class): bool` — query whether a mock or `MockDto` is registered for a class without triggering creation
- `phpstan/phpstan-mockery` extension — resolves `byDefault()` false positives, shrinking the PHPStan baseline from 6 to 3 entries
- `.gitattributes` `export-ignore` rules — exclude `tests/`, `.dev/`, and config from Composer installs
- README — standalone `MockContainerTrait` usage section; PHP version, PHPStan level, code style, and license badges

### Removed

- Stale `AUDIT_REPORT.md` (outdated v1.1.3 report, replaced by CHANGELOG)

## [v2.1.1] - 2026-03-30 - Tighten MockContainer typing and reduce PHPStan baseline

### Fixed

- `.dev/utility.sh` — `check_container()` fall-through logic corrected so the container health check exits with the right status

### Changed

- `MockContainer::createMock()` — wrap body in `try/finally` so the circular-dependency guard state is cleared even when a construction exception is thrown
- `EventDispatcherInterfaceMock`, `SluggerInterfaceMock`, `ManagerRegistryMock` — standardize on `self::` over `static::` for internal factory calls
- `MockDto` — add `class-string` PHPDoc on the constructor argument and `getClass()` return
- `tests/TestCase/MockContainerTraitTest.php` — extract `MockContainerTraitTestCase` / `MockContainerTraitTearDownTestCase` concrete doubles (replacing anonymous classes), shrinking PHPStan baseline from 13 to 6
- README code samples — add missing `use` import statements so copy-paste examples type-check

## [v2.1.0] - 2026-03-30 - Add circular dependency guard and registerMock() trait API

### Changed

- Composer version constraints tightened from wildcard (`1.*`) to caret (`^1.0`) notation across `composer.json`
- README `registerMock()` example — use the public trait API instead of accessing private `$this->mockContainer`
- README dev section — replace the non-existent `./dc` script with actual `docker compose` commands
- README — add `CircularDependencyException` to the exceptions table
- `.dev/docker/docker-compose.yml` — drop `privileged: true` from the dev container (not needed)
- `phpstan-baseline.neon` — regenerated against updated line numbers

### Added

- `MockContainer::createMock()` — circular dependency detection; throws `CircularDependencyException` instead of recursing infinitely when mocks reference each other via `construct`
- `MockContainerTrait::registerMock()` — register a pre-built `MockInterface` instance directly from the test case without going through a `MockDto`
- `MockeryPHPUnitIntegration` trait added to all standalone test classes so Mockery expectations are verified and state is cleaned up automatically
- `phpunit.xml.dist` — `failOnRisky="true"`, `failOnWarning="true"`
- New test fixtures: `tests/Utility/CircularAlphaMock.php`, `tests/Utility/CircularBetaMock.php`, plus a `CircularDependencyException` case in `MockContainerEdgeCaseTest`
- New exception: `src/Exception/CircularDependencyException.php`

## [v2.0.4] - 2026-03-30 - Fix README close() description and clean up pre-commit hook

### Fixed

- README — `close()` method description no longer claims it calls `Mockery::close()` directly (since v2.0.3 the call is delegated to `MockeryPHPUnitIntegration`)

### Changed

- `.dev/git-hooks/pre-commit` — `php_cs_fixer()` takes the target path as a positional parameter instead of reading a global, the unused argument is dropped from the `php_unit` call, and error paths consistently call `stop()`
- `dc` wrapper — guard the git-hooks symlink refresh so `rm -rf && ln -s` no longer runs on every invocation

### Removed

- Orphan `phpcs.xml` config — no `squizlabs/php_codesniffer` dependency exists (removed in v2.0.1)

## [v2.0.3] - 2026-03-29 - Delegate Mockery lifecycle to MockeryPHPUnitIntegration

### Changed

- `MockContainer::close()` — drop the explicit `Mockery::close()` call; the lifecycle is now driven by `MockeryPHPUnitIntegration` which the trait pulls in, so expectations are still verified but PHPUnit controls the shutdown order
- `MockContainerTrait` — use `MockeryPHPUnitIntegration` alongside the container, ensuring `close()` runs automatically through the trait's `#[After]` hook
- `phpstan.neon` — add `vendor/bin/.phpunit/` to `scanDirectories` so Symfony PHPUnit Bridge helpers are type-resolved

## [v2.0.2] - 2026-03-28 - Clean up dev infrastructure, expand docs, and normalize phpunit.xml.dist

### Changed

- `composer.json` — sharpen `description`, normalize `phpstan/phpstan` version constraint
- `phpunit.xml` → `phpunit.xml.dist` (distributed template); add `phpunit.xml` to `.gitignore` so local overrides stay out of VCS
- Update PHPUnit XML schema reference from 9.3 to 9.6
- Mock closures (`EventDispatcherInterfaceMock`, `SluggerInterfaceMock`, `ManagerRegistryMock`) and test callbacks — use `static function` where `$this` is not captured
- `tests/Container/MockContainerTest.php` — rename test method to the more descriptive `testPartialMockWithConstructDependenciesResolvesCorrectly`
- `.dev/git-hooks/pre-commit` — rename `error()` to `print_error()` and drop `code_sniffer()` (no PHP_CodeSniffer dep)
- `.dev/utility.sh` — drop the unused `error()` function
- `.dev/docker/.profile` — add `pstan()` shell helper

### Added

- README expansion — runtime mock registration examples, exceptions table, clarification of the `construct` parameter behavior
- First CHANGELOG.md file following Keep-a-Changelog, with backfilled history for v1.0.0 through v2.0.1

## [v2.0.1] - 2026-03-28 - Remove unused PHP_CodeSniffer dep, add suggest section, use static closures

### Changed

- Mock closures — use `static function` in `ManagerRegistryMock` (and remaining test utilities) where `$this` is not referenced
- `phpstan.neon` — introduce `phpstan-baseline.neon` with 40 tracked entries so level 8 is enforced on new code while legacy signatures are tolerated
- README — add PHPStan usage section and clarify installation/setup

### Added

- `composer.json` `suggest` block — document optional deps used only by specific mocks: `doctrine/orm` and `doctrine/doctrine-bundle` (for `ManagerRegistryMock`), `symfony/string` (for `SluggerInterfaceMock`)
- `tests/Utility/EntityWithSetId.php` — fixture for `ManagerRegistryMock::getReference()` tests that need `setId()`

### Removed

- `squizlabs/php_codesniffer` dev dependency — no rule set configured, superseded by `friendsofphp/php-cs-fixer`

## [v2.0.0] - 2026-03-27 - Introduce typed exceptions, PHPStan level 8, and dev infrastructure

### Breaking Changes

- Generic `Exception` replaced with typed exception classes: `MockAlreadyRegisteredException`, `MockNotFoundException`, `MockContainerNotInitializedException`, `ClassNotFoundException`. Code catching `PrecisionSoft\Symfony\Phpunit\Exception\Exception` must be updated to catch the specific subclass
- `ManagerRegistryMock::getEntityManagerMock()`, `getClassMetadataMock()`, `getConnectionMock()` visibility tightened from `public` to `private` — they are factory helpers, not API
- `EntityManagerInterface` mock methods `persist`, `flush`, `beginTransaction`, `commit`, `rollback`, `remove`, `clear` now `andReturnNull()` instead of `andReturnSelf()` to match the real `void` return type. Tests that chained on these return values must be updated
- `SluggerInterfaceMock::slug()` returns `UnicodeString($inputString)` instead of `UnicodeString(\uniqid())` — results are now deterministic; tests asserting randomness must be updated
- `EventDispatcherInterfaceMock::dispatch()` closure signature changed from `func_get_arg(0)` to a typed `object $event` parameter
- `MockDto::getConstruct()` return type narrowed from `MockDtoInterface[]|string[]|null` to `list<MockDto|MockDtoInterface|class-string<MockDtoInterface>|scalar>|null`

### Fixed

- `MockContainer` — mock behavior inconsistencies around recursive resolution, null lookups, and construct-branch coverage (see Added/Changed for specifics)

### Changed

- `MockContainer` — new `getOrCreateMock()` internal path handles recursive dependency resolution cleanly; `createMock()` refactored from `switch` to an explicit `if` chain so PHPStan can narrow each branch
- `ManagerRegistryMock` — harden mock definitions and align naming (`$mock` → `$mockInterface` across all mocks)
- Project title in README normalized from `symfony-phpunit` to `Symfony Phpunit`

### Added

- PHPStan level 8 static analysis with `phpstan-baseline.neon`
- `php-cs-fixer` PER-CS2.0 code style enforcement (`.php-cs-fixer.dist.php`)
- New exception classes under `src/Exception/` (see Breaking)
- `tests/Utility/TestKernel.php` and per-kernel test utilities (`MixedConstructorDto`, `ScalarConstructorDto`, `ThirdMockDtoInterface`)
- `tests/TestCase/AbstractKernelTestCaseTest.php` and `tests/TestCase/MockContainerTraitTest.php` — coverage for kernel-based testing and trait integration
- Dev infrastructure: `.dev/docker/entrypoint.sh`, expanded pre-commit hook, utility scripts

## [v1.1.3] - 2026-03-20 - Support required constructors in ManagerRegistryMock::getReference

### Fixed

- `ManagerRegistryMock::getReference()` — use `ReflectionClass::newInstanceWithoutConstructor()` when the referenced class has a required constructor, so the mock no longer crashes on entities with non-optional `__construct` parameters
- `tests/Mock/ManagerRegistryMockTest.php` — add coverage for `getReference()` against an entity with a required constructor

## [v1.1.2] - 2026-03-19 - Narrow MockDto return types and guard uninitialized MockContainerTrait

### Fixed

- `MockDto::getClass()` return type narrowed from `?string` to `string` (the class is always required at construction)
- `MockDto::getPartial()` return type narrowed from `?bool` to `bool`
- `MockContainerTrait::get()` now throws `Exception('mock container is not initialized')` when `$mockContainer` is null, replacing the implicit null method call with a clear error

### Changed

- `README.md` — drop the stale `Todo: Unit tests` section (unit tests landed in v1.1.0)

## [v1.1.1] - 2026-03-19 - Move dev scripts to .dev and validate class existence

### Fixed

- `ManagerRegistryMock` — validate the class passed to `getReference()` via `class_exists()` before instantiating, and remove the duplicate `Mockery::mock()` call for `ClassMetadata`
- `MockContainer` — tighten lookup logic so missing mocks surface a clear exception instead of a silent `null`
- `composer.json` — fix metadata and bump patch version

### Changed

- Dev infrastructure moved from `dev/` to `.dev/` so non-production files stay out of the top-level namespace: `dev/docker/**` → `.dev/docker/**`, `dev/git-hooks/**` → `.dev/git-hooks/**`, `dev/utility.sh` → `.dev/utility.sh`
- `dc` wrapper — update paths to reference `.dev/docker/`

## [v1.1.0] - 2026-03-18 - Apply Yoda style rules and add mock unit tests

### Fixed

- `ManagerRegistryMock` — correct the `ClassMetadata` type returned by `getClassMetadata()`
- `.dev/git-hooks/pre-commit` — fix hook execution and argument handling

### Changed

- Project-wide PHP code style pass: enforce Yoda comparisons (`null === $x`), replace implicit boolean coercion with explicit `false ===` / `null !==` checks, remove `!` negation

### Added

- Unit tests: `tests/Mock/ManagerRegistryMockTest.php`, `tests/Mock/EventDispatcherInterfaceMockTest.php`, `tests/Mock/SluggerInterfaceMockTest.php`, `tests/MockDtoTest.php`, `tests/TestCase/AbstractTestCaseTest.php`, `tests/Container/MockContainerEdgeCaseTest.php`

## [v1.0.1] - 2025-10-25 - Refine php-cs-fixer config and install git in dev image

### Changed

- `.php-cs-fixer.dist.php` — add `cast_spaces: none` rule so `(int)$value` no longer becomes `(int) $value`
- `.dev/docker/Dockerfile` — install `git` package (needed by Composer and hooks) in the Alpine base image
- Refresh Composer lock file against upstream dependency updates

## [v1.0.0] - 2024-09-17 - Initial release

### Added

- Initial public release of the Symfony PHPUnit mock toolkit
- `MockDto` configuration pattern for declarative mock setup
- `MockContainer` for mock lifecycle management with lazy creation and recursive dependency resolution
- `AbstractTestCase` and `AbstractKernelTestCase` base test classes
- `MockContainerTrait` for flexible test integration
- Built-in mocks: `ManagerRegistryMock`, `SluggerInterfaceMock`, `EventDispatcherInterfaceMock`
- Dev infrastructure (Docker compose, pre-commit hook, utility scripts) under `dev/`

[Unreleased]: https://github.com/precision-soft/symfony-phpunit/compare/v3.4.2...HEAD

[v3.4.2]: https://github.com/precision-soft/symfony-phpunit/compare/v3.4.1...v3.4.2

[v3.4.1]: https://github.com/precision-soft/symfony-phpunit/compare/v3.4.0...v3.4.1

[v3.4.0]: https://github.com/precision-soft/symfony-phpunit/compare/v3.3.1...v3.4.0

[v3.3.1]: https://github.com/precision-soft/symfony-phpunit/compare/v3.3.0...v3.3.1

[v3.3.0]: https://github.com/precision-soft/symfony-phpunit/compare/v3.2.1...v3.3.0

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

[v2.0.0]: https://github.com/precision-soft/symfony-phpunit/compare/v1.1.3...v2.0.0

[v1.1.3]: https://github.com/precision-soft/symfony-phpunit/compare/v1.1.2...v1.1.3

[v1.1.2]: https://github.com/precision-soft/symfony-phpunit/compare/v1.1.1...v1.1.2

[v1.1.1]: https://github.com/precision-soft/symfony-phpunit/compare/v1.1.0...v1.1.1

[v1.1.0]: https://github.com/precision-soft/symfony-phpunit/compare/v1.0.1...v1.1.0

[v1.0.1]: https://github.com/precision-soft/symfony-phpunit/compare/v1.0.0...v1.0.1

[v1.0.0]: https://github.com/precision-soft/symfony-phpunit/releases/tag/v1.0.0
