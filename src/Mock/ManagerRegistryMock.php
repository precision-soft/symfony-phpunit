<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Mock;

use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Mockery;
use Mockery\MockInterface;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\Contract\MockDtoInterface;
use PrecisionSoft\Symfony\Phpunit\Exception\ClassNotFoundException;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use ReflectionClass;

class ManagerRegistryMock implements MockDtoInterface
{
    /**
     * @deprecated since 3.3.0, use configureManagedEntityClasses() for per-mock scoping. Will be removed in 4.0.0.
     * @var array<string, true>
     */
    protected static array $managedEntityClasses = [];

    public static function getMockDto(): MockDto
    {
        return new MockDto(
            ManagerRegistry::class,
            null,
            false,
            static::getOnCreate(),
        );
    }

    /**
     * Restricts `getManagerForClass()` on the given mock to return the entity manager only for the listed entity
     * classes, `null` otherwise. Configuration is per-mock (no static state), safe for parallel test execution.
     *
     * @param MockInterface&ManagerRegistry $managerRegistryMock
     * @param list<class-string> $entityClasses
     */
    public static function configureManagedEntityClasses(
        MockInterface $managerRegistryMock,
        array $entityClasses,
    ): void {
        $classSet = [];

        foreach ($entityClasses as $entityClass) {
            $classSet[$entityClass] = true;
        }

        $entityManagerMock = $managerRegistryMock->getManager();

        $managerRegistryMock->shouldReceive('getManagerForClass')
            ->andReturnUsing(
                static function (string $className) use ($entityManagerMock, $classSet): ?object {
                    if (true === isset($classSet[$className])) {
                        return $entityManagerMock;
                    }

                    return null;
                },
            );
    }

    /**
     * @param list<class-string> $entityClasses
     * @deprecated since 3.3.0, use configureManagedEntityClasses() for per-mock scoping. Will be removed in 4.0.0.
     */
    public static function setManagedEntityClasses(array $entityClasses): void
    {
        self::$managedEntityClasses = [];

        foreach ($entityClasses as $entityClass) {
            self::$managedEntityClasses[$entityClass] = true;
        }
    }

    /**
     * @deprecated since 3.3.0, use configureManagedEntityClasses() for per-mock scoping. Will be removed in 4.0.0.
     */
    public static function resetManagedEntityClasses(): void
    {
        self::$managedEntityClasses = [];
    }

    public static function getOnCreate(): Closure
    {
        return static function (MockInterface $mockInterface, MockContainer $mockContainer): void {
            $entityManagerMock = static::getEntityManagerMock($mockContainer);
            $connectionMock = static::getConnectionMock($mockContainer);

            $mockInterface->shouldReceive('getManager')
                ->byDefault()
                ->andReturn($entityManagerMock);

            $mockInterface->shouldReceive('getManagerForClass')
                ->byDefault()
                ->andReturnUsing(
                    static function (string $className) use ($entityManagerMock): ?MockInterface {
                        if (0 === \count(self::$managedEntityClasses)) {
                            return $entityManagerMock;
                        }

                        if (true === isset(self::$managedEntityClasses[$className])) {
                            return $entityManagerMock;
                        }

                        return null;
                    },
                );

            $mockInterface->shouldReceive('getDefaultManagerName')
                ->byDefault()
                ->andReturn('default');

            $mockInterface->shouldReceive('getManagers')
                ->byDefault()
                ->andReturn(['default' => $entityManagerMock]);

            $mockInterface->shouldReceive('getManagerNames')
                ->byDefault()
                ->andReturn(['default' => 'doctrine.orm.default_entity_manager']);

            $mockInterface->shouldReceive('resetManager')
                ->byDefault()
                ->andReturn($entityManagerMock);

            $mockInterface->shouldReceive('getDefaultConnectionName')
                ->byDefault()
                ->andReturn('default');

            $mockInterface->shouldReceive('getConnections')
                ->byDefault()
                ->andReturn(['default' => $connectionMock]);

            $mockInterface->shouldReceive('getConnectionNames')
                ->byDefault()
                ->andReturn(['default' => 'doctrine.dbal.default_connection']);
        };
    }

    protected static function getEntityManagerMock(MockContainer $mockContainer): MockInterface
    {
        $repositoryMocks = [];

        return $mockContainer->getOrRegisterMock(
            new MockDto(
                EntityManagerInterface::class,
                null,
                false,
                static function (MockInterface $mockInterface, MockContainer $innerMockContainer) use (&$repositoryMocks): void {
                    $mockInterface->shouldReceive('beginTransaction')
                        ->byDefault()
                        ->andReturnNull();

                    $mockInterface->shouldReceive('persist')
                        ->byDefault()
                        ->andReturnNull();

                    $mockInterface->shouldReceive('remove')
                        ->byDefault()
                        ->andReturnNull();

                    $mockInterface->shouldReceive('flush')
                        ->byDefault()
                        ->andReturnNull();

                    $mockInterface->shouldReceive('commit')
                        ->byDefault()
                        ->andReturnNull();

                    $mockInterface->shouldReceive('rollback')
                        ->byDefault()
                        ->andReturnNull();

                    $mockInterface->shouldReceive('clear')
                        ->byDefault()
                        ->andReturnNull();

                    $mockInterface->shouldReceive('getReference')
                        ->byDefault()
                        ->andReturnUsing(
                            static function (string $entityName, mixed $entityId): ?object {
                                if (null === $entityId) {
                                    return null;
                                }

                                if (false === \class_exists($entityName)) {
                                    throw new ClassNotFoundException(\sprintf('class `%s` does not exist', $entityName));
                                }

                                $reflectionClass = new ReflectionClass($entityName);
                                $reflectionMethod = $reflectionClass->getConstructor();

                                if (null !== $reflectionMethod && 0 < $reflectionMethod->getNumberOfRequiredParameters()) {
                                    /** @info entities with readonly constructor-promoted properties will be in an invalid state — acceptable for test references used only as identity markers */
                                    $entity = $reflectionClass->newInstanceWithoutConstructor();
                                } else {
                                    $entity = new $entityName();
                                }

                                if (true === \method_exists($entity, 'setId')) {
                                    $entity->setId($entityId);
                                }

                                return $entity;
                            },
                        );

                    $mockInterface->shouldReceive('getClassMetadata')
                        ->byDefault()
                        ->andReturn(static::getClassMetadataMock($innerMockContainer));

                    $mockInterface->shouldReceive('getRepository')
                        ->byDefault()
                        ->andReturnUsing(
                            static function (string $entityName) use (&$repositoryMocks): MockInterface {
                                if (true === isset($repositoryMocks[$entityName])) {
                                    return $repositoryMocks[$entityName];
                                }

                                $repositoryMock = Mockery::mock(EntityRepository::class);
                                $repositoryMocks[$entityName] = $repositoryMock;

                                return $repositoryMock;
                            },
                        );

                    $mockInterface->shouldReceive('getConnection')
                        ->byDefault()
                        ->andReturn(static::getConnectionMock($innerMockContainer));

                    $mockInterface->shouldReceive('find')
                        ->byDefault()
                        ->andReturnNull();

                    $mockInterface->shouldReceive('detach')
                        ->byDefault()
                        ->andReturnNull();

                    $mockInterface->shouldReceive('refresh')
                        ->byDefault()
                        ->andReturnNull();

                    $mockInterface->shouldReceive('contains')
                        ->byDefault()
                        ->andReturn(true);

                    $mockInterface->shouldReceive('close')
                        ->byDefault()
                        ->andReturnNull();

                    $mockInterface->shouldReceive('isOpen')
                        ->byDefault()
                        ->andReturn(true);

                    $mockInterface->shouldReceive('lock')
                        ->byDefault()
                        ->andReturnNull();

                    $mockInterface->shouldReceive('wrapInTransaction')
                        ->byDefault()
                        ->andReturnUsing(
                            static function (callable $callback) use ($mockInterface): mixed {
                                return $callback($mockInterface);
                            },
                        );

                    $mockInterface->shouldReceive('createQuery')
                        ->byDefault()
                        ->andReturnUsing(static fn(): Query => Mockery::mock(Query::class));

                    $mockInterface->shouldReceive('createQueryBuilder')
                        ->byDefault()
                        ->andReturnUsing(static fn(): QueryBuilder => Mockery::mock(QueryBuilder::class));

                    $mockInterface->shouldReceive('createNativeQuery')
                        ->byDefault()
                        ->andReturnUsing(
                            static fn(string $sql, ResultSetMapping $rsm): NativeQuery => Mockery::mock(NativeQuery::class),
                        );

                    $mockInterface->shouldReceive('getUnitOfWork')
                        ->byDefault()
                        ->andReturnUsing(static fn(): UnitOfWork => Mockery::mock(UnitOfWork::class));

                    $mockInterface->shouldReceive('getConfiguration')
                        ->byDefault()
                        ->andReturnUsing(static fn(): Configuration => Mockery::mock(Configuration::class));
                },
            ),
        );
    }

    protected static function getClassMetadataMock(MockContainer $mockContainer): MockInterface
    {
        return $mockContainer->getOrRegisterMock(
            new MockDto(
                ClassMetadata::class,
                [\stdClass::class],
                false,
                static function (MockInterface $mockInterface): void {
                    $mockInterface->shouldReceive('setIdGeneratorType')
                        ->byDefault()
                        ->andReturnNull();

                    $mockInterface->shouldReceive('setIdGenerator')
                        ->byDefault()
                        ->andReturnNull();
                },
            ),
        );
    }

    protected static function getConnectionMock(MockContainer $mockContainer): MockInterface
    {
        return $mockContainer->getOrRegisterMock(
            new MockDto(
                Connection::class,
                null,
                false,
                static function (MockInterface $mockInterface, MockContainer $innerMockContainer): void {
                    $mockInterface->shouldReceive('executeStatement')
                        ->byDefault()
                        ->andReturn(1);
                },
            ),
        );
    }
}
