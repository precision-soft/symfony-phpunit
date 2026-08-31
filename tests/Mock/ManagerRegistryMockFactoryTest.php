<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Mock;

use ArrayObject;
use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\Exception\MockClassMismatchException;
use PrecisionSoft\Symfony\Phpunit\Mock\ManagerRegistryMock;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\EntityWithSetId;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\ExposedManagerRegistryMock;
use stdClass;

/**
 * @internal
 */
final class ManagerRegistryMockFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private MockContainer $mockContainer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockContainer = new MockContainer();
        $this->mockContainer->registerMockDto(ManagerRegistryMock::getMockDto());
    }

    protected function tearDown(): void
    {
        $this->mockContainer->close();

        parent::tearDown();
    }

    public function testRepositoryFactoryIsLazyScopedAndCachedPerEntity(): void
    {
        $entityManagerMock = $this->getEntityManagerMock();
        $requestedEntityClasses = [];

        /** @var Closure(string): (MockInterface&EntityRepository<object>) $repositoryFactory */
        $repositoryFactory = static function (string $entityClass) use (
            &$requestedEntityClasses,
        ): MockInterface {
            $requestedEntityClasses[] = $entityClass;

            return Mockery::mock(EntityRepository::class);
        };

        ManagerRegistryMock::configureRepositoryFactory($entityManagerMock, $repositoryFactory);

        $repositoryMockInterface = $entityManagerMock->getRepository(stdClass::class);
        $cachedRepositoryMockInterface = $entityManagerMock->getRepository(stdClass::class);
        $otherRepositoryMockInterface = $entityManagerMock->getRepository(EntityWithSetId::class);

        static::assertSame($repositoryMockInterface, $cachedRepositoryMockInterface);
        static::assertNotSame($repositoryMockInterface, $otherRepositoryMockInterface);
        static::assertSame([stdClass::class, EntityWithSetId::class], $requestedEntityClasses);
    }

    public function testClassMetadataFactoryIsLazyScopedAndCachedPerEntity(): void
    {
        $entityManagerMock = $this->getEntityManagerMock();
        $requestedEntityClasses = [];

        /** @var Closure(string): (MockInterface&ClassMetadata<object>) $classMetadataFactory */
        $classMetadataFactory = static function (string $entityClass) use (
            &$requestedEntityClasses,
        ): MockInterface {
            $requestedEntityClasses[] = $entityClass;

            return Mockery::mock(ClassMetadata::class, [$entityClass]);
        };

        ManagerRegistryMock::configureClassMetadataFactory($entityManagerMock, $classMetadataFactory);

        $classMetadataMockInterface = $entityManagerMock->getClassMetadata(stdClass::class);
        $cachedClassMetadataMockInterface = $entityManagerMock->getClassMetadata(stdClass::class);
        $otherClassMetadataMockInterface = $entityManagerMock->getClassMetadata(EntityWithSetId::class);

        static::assertSame($classMetadataMockInterface, $cachedClassMetadataMockInterface);
        static::assertNotSame($classMetadataMockInterface, $otherClassMetadataMockInterface);
        static::assertSame([stdClass::class, EntityWithSetId::class], $requestedEntityClasses);
    }

    public function testRepositoryFactoryRejectsANonMockReturnValue(): void
    {
        $entityManagerMock = $this->getEntityManagerMock();

        ExposedManagerRegistryMock::configureCachedFactory(
            $entityManagerMock,
            'getRepository',
            EntityRepository::class,
            static fn(string $entityClass): mixed => null,
        );

        $caughtException = null;

        try {
            $entityManagerMock->getRepository(stdClass::class);
        } catch (MockClassMismatchException $exception) {
            $caughtException = $exception;
        }

        static::assertInstanceOf(MockClassMismatchException::class, $caughtException);
        static::assertSame(
            \sprintf('factory must return a mock of class `%s`', EntityRepository::class),
            $caughtException->getMessage(),
        );
        static::assertSame(
            [
                'entityClass' => stdClass::class,
                'expectedClass' => EntityRepository::class,
                'actualClass' => 'null',
            ],
            $caughtException->getContext(),
        );
    }

    public function testClassMetadataFactoryRejectsAMockOfTheWrongClass(): void
    {
        $entityManagerMock = $this->getEntityManagerMock();

        /** @var Closure(string): (MockInterface&ClassMetadata<object>) $classMetadataFactory */
        $classMetadataFactory = static fn(string $entityClass): mixed => Mockery::mock(ArrayObject::class);

        ManagerRegistryMock::configureClassMetadataFactory($entityManagerMock, $classMetadataFactory);

        $this->expectException(MockClassMismatchException::class);
        $this->expectExceptionMessage(
            \sprintf('factory must return a mock of class `%s`', ClassMetadata::class),
        );

        $entityManagerMock->getClassMetadata(stdClass::class);
    }

    /** @return EntityManagerInterface&MockInterface */
    private function getEntityManagerMock(): EntityManagerInterface
    {
        $managerRegistryMock = $this->mockContainer->getMock(ManagerRegistry::class);

        $entityManagerMock = $managerRegistryMock->getManager();

        static::assertInstanceOf(EntityManagerInterface::class, $entityManagerMock);
        static::assertInstanceOf(MockInterface::class, $entityManagerMock);

        return $entityManagerMock;
    }
}
