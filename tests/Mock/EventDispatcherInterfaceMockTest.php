<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Mock;

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
        $mock = $this->mockContainer->getMock(EventDispatcherInterface::class);

        static::assertInstanceOf(EventDispatcherInterface::class, $mock);
    }

    public function testDispatchReturnsEvent(): void
    {
        $mock = $this->mockContainer->getMock(EventDispatcherInterface::class);

        $event = new stdClass();
        $result = $mock->dispatch($event);

        static::assertSame($event, $result);
    }
}
