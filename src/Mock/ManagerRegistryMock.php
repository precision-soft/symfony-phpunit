<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Mock;

use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Mockery\MockInterface;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\Contract\MockDtoInterface;
use PrecisionSoft\Symfony\Phpunit\Exception\ClassNotFoundException;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use ReflectionClass;

class ManagerRegistryMock implements MockDtoInterface
{
    public static function getMockDto(): MockDto
    {
        return new MockDto(
            ManagerRegistry::class,
            null,
            false,
            self::getOnCreate(),
        );
    }

    public static function getOnCreate(): Closure
    {
        return static function (MockInterface $mockInterface, MockContainer $mockContainer): void {
            $mockInterface->shouldReceive('getManager')
                ->byDefault()
                ->andReturn(self::getEntityManagerMock($mockContainer));
        };
    }

    private static function getEntityManagerMock(MockContainer $mockContainer): MockInterface
    {
        $mockContainer->registerMockDto(
            new MockDto(
                EntityManagerInterface::class,
                [],
                false,
                static function (MockInterface $mockInterface, MockContainer $mockContainer): void {
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
                            static function (string $entityName, mixed $id): object {
                                if (false === class_exists($entityName)) {
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
                                    $entity->setId($id);
                                }

                                return $entity;
                            },
                        );

                    $mockInterface->shouldReceive('getClassMetadata')
                        ->byDefault()
                        ->andReturn(self::getClassMetadataMock($mockContainer));

                    $mockInterface->shouldReceive('getConnection')
                        ->byDefault()
                        ->andReturn(self::getConnectionMock($mockContainer));
                },
            ),
        );

        return $mockContainer->getMock(EntityManagerInterface::class);
    }

    private static function getClassMetadataMock(MockContainer $mockContainer): MockInterface
    {
        $mockContainer->registerMockDto(
            new MockDto(
                ClassMetadata::class,
                null,
                false,
                static function (MockInterface $mockInterface, MockContainer $mockContainer): void {
                    $mockInterface->shouldReceive('setIdGeneratorType')
                        ->byDefault()
                        ->andReturnSelf();

                    $mockInterface->shouldReceive('setIdGenerator')
                        ->byDefault()
                        ->andReturnSelf();
                },
            ),
        );

        return $mockContainer->getMock(ClassMetadata::class);
    }

    private static function getConnectionMock(MockContainer $mockContainer): MockInterface
    {
        $mockContainer->registerMockDto(
            new MockDto(
                Connection::class,
                null,
                true,
                static function (MockInterface $mockInterface, MockContainer $mockContainer): void {
                    $mockInterface->shouldReceive('executeStatement')
                        ->byDefault()
                        ->andReturn(1);
                },
            ),
        );

        return $mockContainer->getMock(Connection::class);
    }
}
