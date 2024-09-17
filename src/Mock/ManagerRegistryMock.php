<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Mock;

use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Mockery\MockInterface;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\Contract\MockDtoInterface;
use PrecisionSoft\Symfony\Phpunit\MockDto;

class ManagerRegistryMock implements MockDtoInterface
{
    public static function getMockDto(): MockDto
    {
        return new MockDto(
            ManagerRegistry::class,
            null,
            false,
            static::getOnCreate(),
        );
    }

    public static function getOnCreate(): Closure
    {
        return function (MockInterface $mock, MockContainer $mockContainer): void {
            $mock->shouldReceive('getManager')
                ->byDefault()
                ->andReturn(static::getEntityManagerMock($mockContainer));
        };
    }

    public static function getEntityManagerMock(MockContainer $mockContainer): MockInterface
    {
        $mockContainer->registerMockDto(
            new MockDto(
                EntityManagerInterface::class,
                [],
                false,
                function (MockInterface $mock, MockContainer $mockContainer): void {
                    $mock->shouldReceive('beginTransaction')
                        ->byDefault()
                        ->andReturnSelf();

                    $mock->shouldReceive('persist')
                        ->byDefault()
                        ->andReturnSelf();

                    $mock->shouldReceive('remove')
                        ->byDefault()
                        ->andReturnSelf();

                    $mock->shouldReceive('flush')
                        ->byDefault()
                        ->andReturnSelf();

                    $mock->shouldReceive('commit')
                        ->byDefault()
                        ->andReturnSelf();

                    $mock->shouldReceive('rollback')
                        ->byDefault()
                        ->andReturnSelf();

                    $mock->shouldReceive('clear')
                        ->byDefault()
                        ->andReturnSelf();

                    $mock->shouldReceive('getReference')
                        ->byDefault()
                        ->andReturnUsing(
                            function (string $entityName, mixed $id): object {
                                $entity = new $entityName();

                                if (\method_exists($entity, 'setId')) {
                                    $entity->setId($id);
                                }

                                return $entity;
                            },
                        );

                    $mock->shouldReceive('getClassMetadata')
                        ->byDefault()
                        ->andReturn(static::getClassMetadataMock($mockContainer));

                    $mock->shouldReceive('getConnection')
                        ->byDefault()
                        ->andReturn(static::getConnectionMock($mockContainer));
                },
            ),
        );

        return $mockContainer->getMock(EntityManagerInterface::class);
    }

    public static function getClassMetadataMock(MockContainer $mockContainer): MockInterface
    {
        $mockContainer->registerMockDto(
            new MockDto(
                ClassMetadata::class,
                [],
                false,
                function (MockInterface $mock, MockContainer $mockContainer): void {
                    $mock->shouldReceive('setIdGeneratorType')
                        ->byDefault()
                        ->andReturnSelf();

                    $mock->shouldReceive('setIdGenerator')
                        ->byDefault()
                        ->andReturnSelf();
                },
            ),
        );

        return $mockContainer->getMock(ClassMetadata::class);
    }

    public static function getConnectionMock(MockContainer $mockContainer): MockInterface
    {
        $mockContainer->registerMockDto(
            new MockDto(
                Connection::class,
                null,
                true,
                function (MockInterface $mock, MockContainer $mockContainer): void {
                    $mock->shouldReceive('executeStatement')
                        ->byDefault()
                        ->andReturn(1);
                },
            ),
        );

        return $mockContainer->getMock(Connection::class);
    }
}
