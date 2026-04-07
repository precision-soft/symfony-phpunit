<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Mock;

use ArrayObject;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\Exception\ClassNotFoundException;
use PrecisionSoft\Symfony\Phpunit\Mock\ManagerRegistryMock;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\EntityWithSetId;
use stdClass;

/**
 * @internal
 */
final class ManagerRegistryMockTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private MockContainer $mockContainer;

    protected function setUp(): void
    {
        parent::setUp();

        ManagerRegistryMock::resetManagedEntityClasses();
        $this->mockContainer = new MockContainer();
        $this->mockContainer->registerMockDto(ManagerRegistryMock::getMockDto());
    }

    protected function tearDown(): void
    {
        $this->mockContainer->close();
        ManagerRegistryMock::resetManagedEntityClasses();

        parent::tearDown();
    }

    public function testGetMockDtoTargetsManagerRegistry(): void
    {
        $mockDto = ManagerRegistryMock::getMockDto();

        static::assertSame(ManagerRegistry::class, $mockDto->getClass());
    }

    public function testMockImplementsManagerRegistry(): void
    {
        $mockInterface = $this->mockContainer->getMock(ManagerRegistry::class);

        static::assertInstanceOf(ManagerRegistry::class, $mockInterface);
    }

    public function testGetManagerReturnsEntityManagerInterface(): void
    {
        $registry = $this->mockContainer->getMock(ManagerRegistry::class);

        $entityManager = $registry->getManager();

        static::assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function testEntityManagerIsRegisteredInContainer(): void
    {
        $this->mockContainer->getMock(ManagerRegistry::class);

        $entityManager = $this->mockContainer->getMock(EntityManagerInterface::class);

        static::assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function testEntityManagerPersistIsCallable(): void
    {
        $registry = $this->mockContainer->getMock(ManagerRegistry::class);
        $entityManager = $registry->getManager();

        $entityManager->persist(new stdClass());

        static::assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function testEntityManagerRemoveIsCallable(): void
    {
        $registry = $this->mockContainer->getMock(ManagerRegistry::class);
        $entityManager = $registry->getManager();

        $entityManager->remove(new stdClass());

        static::assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function testEntityManagerFlushIsCallable(): void
    {
        $registry = $this->mockContainer->getMock(ManagerRegistry::class);
        $entityManager = $registry->getManager();

        $entityManager->flush();

        static::assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function testEntityManagerBeginTransactionIsCallable(): void
    {
        $registry = $this->mockContainer->getMock(ManagerRegistry::class);
        $entityManager = $registry->getManager();

        $entityManager->beginTransaction();

        static::assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function testEntityManagerCommitIsCallable(): void
    {
        $registry = $this->mockContainer->getMock(ManagerRegistry::class);
        $entityManager = $registry->getManager();

        $entityManager->commit();

        static::assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function testEntityManagerRollbackIsCallable(): void
    {
        $registry = $this->mockContainer->getMock(ManagerRegistry::class);
        $entityManager = $registry->getManager();

        $entityManager->rollback();

        static::assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function testEntityManagerClearIsCallable(): void
    {
        $registry = $this->mockContainer->getMock(ManagerRegistry::class);
        $entityManager = $registry->getManager();

        $entityManager->clear();

        static::assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function testEntityManagerGetClassMetadataReturnsOrmClassMetadata(): void
    {
        $registry = $this->mockContainer->getMock(ManagerRegistry::class);
        $entityManager = $registry->getManager();

        $classMetadata = $entityManager->getClassMetadata(stdClass::class);

        static::assertInstanceOf(ClassMetadata::class, $classMetadata);
    }

    public function testEntityManagerGetConnectionReturnsConnection(): void
    {
        $registry = $this->mockContainer->getMock(ManagerRegistry::class);
        $entityManager = $registry->getManager();

        $connection = $entityManager->getConnection();

        static::assertInstanceOf(Connection::class, $connection);
    }

    public function testConnectionExecuteStatementReturnsOne(): void
    {
        $registry = $this->mockContainer->getMock(ManagerRegistry::class);
        $entityManager = $registry->getManager();
        $connection = $entityManager->getConnection();

        $result = $connection->executeStatement('SELECT 1');

        static::assertSame(1, $result);
    }

    public function testClassMetadataSetIdGeneratorTypeIsCallable(): void
    {
        $registry = $this->mockContainer->getMock(ManagerRegistry::class);
        $entityManager = $registry->getManager();
        $classMetadata = $entityManager->getClassMetadata(stdClass::class);

        $classMetadata->setIdGeneratorType(1);

        static::assertInstanceOf(ClassMetadata::class, $classMetadata);
    }

    public function testClassMetadataSetIdGeneratorIsCallable(): void
    {
        $registry = $this->mockContainer->getMock(ManagerRegistry::class);
        $entityManager = $registry->getManager();
        $classMetadata = $entityManager->getClassMetadata(stdClass::class);
        $idGenerator = Mockery::mock(AbstractIdGenerator::class);

        $classMetadata->setIdGenerator($idGenerator);

        static::assertInstanceOf(ClassMetadata::class, $classMetadata);
    }

    public function testClassMetadataIsRegisteredInContainer(): void
    {
        $this->mockContainer->getMock(ManagerRegistry::class);

        $classMetadata = $this->mockContainer->getMock(ClassMetadata::class);

        static::assertInstanceOf(ClassMetadata::class, $classMetadata);
    }

    public function testConnectionIsRegisteredInContainer(): void
    {
        $this->mockContainer->getMock(ManagerRegistry::class);

        $connection = $this->mockContainer->getMock(Connection::class);

        static::assertInstanceOf(Connection::class, $connection);
    }

    public function testGetReferenceWithNoArgConstructor(): void
    {
        $registry = $this->mockContainer->getMock(ManagerRegistry::class);
        $entityManager = $registry->getManager();

        $entity = $entityManager->getReference(stdClass::class, 1);

        static::assertInstanceOf(stdClass::class, $entity);
    }

    public function testGetReferenceWithRequiredConstructorParams(): void
    {
        $registry = $this->mockContainer->getMock(ManagerRegistry::class);
        $entityManager = $registry->getManager();

        $entity = $entityManager->getReference(ArrayObject::class, 1);

        static::assertInstanceOf(ArrayObject::class, $entity);
    }

    public function testGetReferenceWithSetIdSetsId(): void
    {
        $registry = $this->mockContainer->getMock(ManagerRegistry::class);
        $entityManager = $registry->getManager();

        /** @var EntityWithSetId $entityWithSetId */
        $entityWithSetId = $entityManager->getReference(EntityWithSetId::class, 42);

        static::assertInstanceOf(EntityWithSetId::class, $entityWithSetId);
        static::assertSame(42, $entityWithSetId->getId());
    }

    public function testGetReferenceWithoutSetIdDoesNotSetId(): void
    {
        $registry = $this->mockContainer->getMock(ManagerRegistry::class);
        $entityManager = $registry->getManager();

        $entity = $entityManager->getReference(stdClass::class, 99);

        static::assertInstanceOf(stdClass::class, $entity);
        static::assertFalse(\method_exists($entity, 'setId'));
    }

    public function testGetReferenceWithNonexistentClassThrows(): void
    {
        $registry = $this->mockContainer->getMock(ManagerRegistry::class);
        $entityManager = $registry->getManager();

        $this->expectException(ClassNotFoundException::class);
        $this->expectExceptionMessage('does not exist');

        $entityManager->getReference('NonExistentClass', 1);
    }

    public function testGetManagerForClassReturnsEntityManagerWhenNoManagedClassesConfigured(): void
    {
        $registry = $this->mockContainer->getMock(ManagerRegistry::class);

        $entityManager = $registry->getManagerForClass(stdClass::class);

        static::assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function testGetManagerForClassReturnsEntityManagerForManagedClass(): void
    {
        ManagerRegistryMock::setManagedEntityClasses([stdClass::class]);

        $this->mockContainer = new MockContainer();
        $this->mockContainer->registerMockDto(ManagerRegistryMock::getMockDto());

        $registry = $this->mockContainer->getMock(ManagerRegistry::class);

        $entityManager = $registry->getManagerForClass(stdClass::class);

        static::assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function testGetManagerForClassReturnsNullForUnmanagedClass(): void
    {
        ManagerRegistryMock::setManagedEntityClasses([EntityWithSetId::class]);

        $this->mockContainer = new MockContainer();
        $this->mockContainer->registerMockDto(ManagerRegistryMock::getMockDto());

        $registry = $this->mockContainer->getMock(ManagerRegistry::class);

        $entityManager = $registry->getManagerForClass(stdClass::class);

        static::assertNull($entityManager);
    }

    public function testGetRepositoryReturnsDifferentMocksForDifferentEntityClasses(): void
    {
        $registry = $this->mockContainer->getMock(ManagerRegistry::class);
        $entityManager = $registry->getManager();

        $stdClassRepository = $entityManager->getRepository(stdClass::class);
        $entityWithSetIdRepository = $entityManager->getRepository(EntityWithSetId::class);

        static::assertInstanceOf(EntityRepository::class, $stdClassRepository);
        static::assertInstanceOf(EntityRepository::class, $entityWithSetIdRepository);
        static::assertNotSame($stdClassRepository, $entityWithSetIdRepository);
    }

    public function testGetRepositoryReturnsSameMockForSameEntityClass(): void
    {
        $registry = $this->mockContainer->getMock(ManagerRegistry::class);
        $entityManager = $registry->getManager();

        $firstRepository = $entityManager->getRepository(stdClass::class);
        $secondRepository = $entityManager->getRepository(stdClass::class);

        static::assertSame($firstRepository, $secondRepository);
    }
}
