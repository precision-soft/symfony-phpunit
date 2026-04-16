<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Mock;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\Mock\EventDispatcherInterfaceMock;
use stdClass;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class EventDispatcherInterfaceMockTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private MockContainer $mockContainer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockContainer = new MockContainer();
        $this->mockContainer->registerMockDto(EventDispatcherInterfaceMock::getMockDto());
    }

    protected function tearDown(): void
    {
        $this->mockContainer->close();

        parent::tearDown();
    }

    public function testGetMockDtoTargetsEventDispatcherInterface(): void
    {
        $mockDto = EventDispatcherInterfaceMock::getMockDto();

        static::assertSame(EventDispatcherInterface::class, $mockDto->getClass());
    }

    public function testGetMockDtoHasNullConstruct(): void
    {
        $mockDto = EventDispatcherInterfaceMock::getMockDto();

        static::assertNull($mockDto->getConstruct());
    }

    public function testGetMockDtoIsNotPartial(): void
    {
        $mockDto = EventDispatcherInterfaceMock::getMockDto();

        static::assertFalse($mockDto->getPartial());
    }

    public function testGetMockDtoHasOnCreateCallback(): void
    {
        $mockDto = EventDispatcherInterfaceMock::getMockDto();

        static::assertNotNull($mockDto->getOnCreate());
    }

    public function testMockImplementsEventDispatcherInterface(): void
    {
        $mockInterface = $this->mockContainer->getMock(EventDispatcherInterface::class);

        static::assertInstanceOf(EventDispatcherInterface::class, $mockInterface);
    }

    public function testDispatchReturnsEvent(): void
    {
        $mockInterface = $this->mockContainer->getMock(EventDispatcherInterface::class);

        $event = new stdClass();
        $result = $mockInterface->dispatch($event);

        static::assertSame($event, $result);
    }

    public function testDispatchAcceptsOptionalEventName(): void
    {
        $mockInterface = $this->mockContainer->getMock(EventDispatcherInterface::class);

        $event = new stdClass();
        $result = $mockInterface->dispatch($event, 'custom.event.name');

        static::assertSame($event, $result);
    }
}
