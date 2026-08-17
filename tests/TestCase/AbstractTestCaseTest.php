<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\TestCase;

use Mockery;
use Mockery\MockInterface;
use PrecisionSoft\Symfony\Phpunit\Mock\EventDispatcherInterfaceMock;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\SecondMockDto;
use PrecisionSoft\Symfony\Phpunit\TestCase\AbstractTestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class AbstractTestCaseTest extends AbstractTestCase
{
    public static function getMockDto(): MockDto
    {
        return new MockDto(SecondMockDto::class);
    }

    public function testGetReturnsRegisteredMock(): void
    {
        $mockInterface = $this->get(SecondMockDto::class);

        static::assertInstanceOf(MockInterface::class, $mockInterface);
        static::assertInstanceOf(SecondMockDto::class, $mockInterface);
    }

    public function testRegisterMockDtoAddsAdditionalMock(): void
    {
        $this->registerMockDto(EventDispatcherInterfaceMock::getMockDto());

        $mockInterface = $this->get(EventDispatcherInterface::class);

        static::assertInstanceOf(MockInterface::class, $mockInterface);
        static::assertInstanceOf(EventDispatcherInterface::class, $mockInterface);
    }

    public function testRegisterMockDtoReturnsSelf(): void
    {
        $result = $this->registerMockDto(EventDispatcherInterfaceMock::getMockDto());

        static::assertSame($this, $result);
    }

    public function testRegisterMockAcceptsAPreBuiltMockFromASubclass(): void
    {
        $eventDispatcherInterfaceMock = Mockery::mock(EventDispatcherInterface::class);

        $result = $this->registerMock(EventDispatcherInterface::class, $eventDispatcherInterfaceMock);

        static::assertSame($this, $result);
        static::assertSame($eventDispatcherInterfaceMock, $this->get(EventDispatcherInterface::class));
    }

    public function testInitializeMockContainerReturnsTheContainerSetUpAlreadyBuilt(): void
    {
        $mockContainer = $this->initializeMockContainer();

        static::assertSame($mockContainer, $this->initializeMockContainer());
        static::assertTrue($mockContainer->hasMock(SecondMockDto::class));
    }
}
