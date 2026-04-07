<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Mock;

use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
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
    /** @var array<string, true> */
    private static array $managedEntityClasses = [];

    public static function getMockDto(): MockDto
    {
        return new MockDto(
            ManagerRegistry::class,
            null,
            false,
            self::getOnCreate(),
        );
    }

    /** @param list<class-string> $entityClasses */
    public static function setManagedEntityClasses(array $entityClasses): void
    {
        self::$managedEntityClasses = [];

        foreach ($entityClasses as $entityClass) {
            self::$managedEntityClasses[$entityClass] = true;
        }
    }

    public static function resetManagedEntityClasses(): void
    {
        self::$managedEntityClasses = [];
    }

    public static function getOnCreate(): Closure
    {
        return static function (MockInterface $mockInterface, MockContainer $mockContainer): void {
            $mockInterface->shouldReceive('getManager')
                ->byDefault()
                ->andReturn(self::getEntityManagerMock($mockContainer));

            $entityManagerMock = self::getEntityManagerMock($mockContainer);

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
        };
    }

    private static function getEntityManagerMock(MockContainer $mockContainer): MockInterface
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
                            static function (string $entityName, mixed $entityId): object {
                                if (false === \class_exists($entityName)) {
                                    throw new ClassNotFoundException(\sprintf('class `%s` does not exist', $entityName));
                                }

                                $reflectionClass = new ReflectionClass($entityName);
                                $reflectionMethod = $reflectionClass->getConstructor();

                                if (null !== $reflectionMethod && 0 < $reflectionMethod->getNumberOfRequiredParameters()) {
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
                        ->andReturn(self::getClassMetadataMock($innerMockContainer));

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
                        ->andReturn(self::getConnectionMock($innerMockContainer));
                },
            ),
        );
    }

    private static function getClassMetadataMock(MockContainer $mockContainer): MockInterface
    {
        return $mockContainer->getOrRegisterMock(
            new MockDto(
                ClassMetadata::class,
                null,
                false,
                static function (MockInterface $mockInterface, MockContainer $innerMockContainer): void {
                    $mockInterface->shouldReceive('setIdGeneratorType')
                        ->byDefault()
                        ->andReturnSelf();

                    $mockInterface->shouldReceive('setIdGenerator')
                        ->byDefault()
                        ->andReturnSelf();
                },
            ),
        );
    }

    private static function getConnectionMock(MockContainer $mockContainer): MockInterface
    {
        return $mockContainer->getOrRegisterMock(
            new MockDto(
                Connection::class,
                null,
                true,
                static function (MockInterface $mockInterface, MockContainer $innerMockContainer): void {
                    $mockInterface->shouldReceive('executeStatement')
                        ->byDefault()
                        ->andReturn(1);
                },
            ),
        );
    }
}
