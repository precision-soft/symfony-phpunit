<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Container;

use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\Exception\Exception;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\SecondMockDto;

/**
 * @internal
 */
final class MockContainerEdgeCaseTest extends TestCase
{
    private MockContainer $mockContainer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockContainer = new MockContainer();
    }

    protected function tearDown(): void
    {
        $this->mockContainer->close();

        parent::tearDown();
    }

    public function testRegisterMockDtoThrowsExceptionOnDuplicate(): void
    {
        $mockDto = new MockDto(SecondMockDto::class);
        $this->mockContainer->registerMockDto($mockDto);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(\sprintf('mock dto already registered for class `%s`', SecondMockDto::class));

        $this->mockContainer->registerMockDto($mockDto);
    }

    public function testGetMockThrowsExceptionWhenNotRegistered(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(\sprintf('no mock dto found for class `%s`', SecondMockDto::class));

        $this->mockContainer->getMock(SecondMockDto::class);
    }

    public function testRegisterMockThrowsExceptionOnDuplicate(): void
    {
        $this->mockContainer->registerMockDto(new MockDto(SecondMockDto::class));
        $mock = $this->mockContainer->getMock(SecondMockDto::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(\sprintf('mock already registered for class `%s`', SecondMockDto::class));

        $this->mockContainer->registerMock(SecondMockDto::class, $mock);
    }

    public function testRegisterMockDirectlyAndRetrieve(): void
    {
        $externalMock = Mockery::mock(SecondMockDto::class);
        $this->mockContainer->registerMock(SecondMockDto::class, $externalMock);

        $retrieved = $this->mockContainer->getMock(SecondMockDto::class);

        static::assertSame($externalMock, $retrieved);
    }

    public function testGetMockReturnsSameInstanceOnSubsequentCalls(): void
    {
        $this->mockContainer->registerMockDto(new MockDto(SecondMockDto::class));

        $firstCall = $this->mockContainer->getMock(SecondMockDto::class);
        $secondCall = $this->mockContainer->getMock(SecondMockDto::class);

        static::assertSame($firstCall, $secondCall);
    }

    public function testOnCreateCallbackIsInvoked(): void
    {
        $callbackInvoked = false;
        $mockDto = new MockDto(
            SecondMockDto::class,
            null,
            false,
            function (MockInterface $mock, MockContainer $container) use (&$callbackInvoked): void {
                $callbackInvoked = true;
            },
        );

        $this->mockContainer->registerMockDto($mockDto);
        $this->mockContainer->getMock(SecondMockDto::class);

        static::assertTrue($callbackInvoked);
    }

    public function testOnCreateCallbackReceivesMockAndContainer(): void
    {
        $receivedMock = null;
        $receivedContainer = null;

        $mockDto = new MockDto(
            SecondMockDto::class,
            null,
            false,
            function (MockInterface $mock, MockContainer $container) use (&$receivedMock, &$receivedContainer): void {
                $receivedMock = $mock;
                $receivedContainer = $container;
            },
        );

        $this->mockContainer->registerMockDto($mockDto);
        $createdMock = $this->mockContainer->getMock(SecondMockDto::class);

        static::assertSame($createdMock, $receivedMock);
        static::assertSame($this->mockContainer, $receivedContainer);
    }

    public function testPartialMockIsCreated(): void
    {
        $this->mockContainer->registerMockDto(new MockDto(SecondMockDto::class, null, true));

        $mock = $this->mockContainer->getMock(SecondMockDto::class);

        static::assertInstanceOf(MockInterface::class, $mock);
        static::assertInstanceOf(SecondMockDto::class, $mock);
    }

    public function testRegisterMockDtoReturnsSelf(): void
    {
        $mockDto = new MockDto(SecondMockDto::class);

        $result = $this->mockContainer->registerMockDto($mockDto);

        static::assertSame($this->mockContainer, $result);
    }

    public function testRegisterMockReturnsSelf(): void
    {
        $externalMock = Mockery::mock(SecondMockDto::class);

        $result = $this->mockContainer->registerMock(SecondMockDto::class, $externalMock);

        static::assertSame($this->mockContainer, $result);
    }
}
