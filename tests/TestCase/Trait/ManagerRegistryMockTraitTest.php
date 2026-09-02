<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\TestCase\Trait;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\Mock\ManagerRegistryMock;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\EntityWithSetId;
use PrecisionSoft\Symfony\Phpunit\TestCase\Trait\ManagerRegistryMockTrait;
use stdClass;

/** @internal */
final class ManagerRegistryMockTraitTest extends TestCase
{
    use ManagerRegistryMockTrait;
    use MockeryPHPUnitIntegration;

    private MockContainer $mockContainer;

    public function testSetManagedEntityClassesRestrictsGetManagerForClass(): void
    {
        $managerRegistry = $this->mockContainer->getMock(ManagerRegistry::class);

        ManagerRegistryMock::setManagedEntityClasses([EntityWithSetId::class]);

        static::assertInstanceOf(
            EntityManagerInterface::class,
            $managerRegistry->getManagerForClass(EntityWithSetId::class),
        );
        static::assertNull($managerRegistry->getManagerForClass(stdClass::class));
    }

    public function testResetManagedEntityClassesRestoresTheDefaultFallback(): void
    {
        $managerRegistry = $this->mockContainer->getMock(ManagerRegistry::class);

        ManagerRegistryMock::setManagedEntityClasses([EntityWithSetId::class]);

        static::assertNull($managerRegistry->getManagerForClass(stdClass::class));

        ManagerRegistryMock::resetManagedEntityClasses();

        static::assertInstanceOf(
            EntityManagerInterface::class,
            $managerRegistry->getManagerForClass(stdClass::class),
        );
    }

    public function testTheTraitHookResetsTheManagedEntityClasses(): void
    {
        $managerRegistry = $this->mockContainer->getMock(ManagerRegistry::class);

        ManagerRegistryMock::setManagedEntityClasses([EntityWithSetId::class]);

        static::assertNull($managerRegistry->getManagerForClass(stdClass::class));

        $this->resetManagerRegistryMockState();

        static::assertInstanceOf(
            EntityManagerInterface::class,
            $managerRegistry->getManagerForClass(stdClass::class),
        );
    }

    public function testTheHookRunsBetweenTestsWithoutBeingCalled(): void
    {
        $managerRegistry = $this->mockContainer->getMock(ManagerRegistry::class);

        ManagerRegistryMock::setManagedEntityClasses([EntityWithSetId::class]);

        static::assertNull($managerRegistry->getManagerForClass(stdClass::class));
    }

    #[Depends('testTheHookRunsBetweenTestsWithoutBeingCalled')]
    public function testTheDefaultFallbackIsBackInTheNextTest(): void
    {
        $managerRegistry = $this->mockContainer->getMock(ManagerRegistry::class);

        static::assertInstanceOf(
            EntityManagerInterface::class,
            $managerRegistry->getManagerForClass(stdClass::class),
        );
    }

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
}
