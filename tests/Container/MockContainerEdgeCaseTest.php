<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Container;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\Exception\CircularDependencyException;
use PrecisionSoft\Symfony\Phpunit\Exception\MockAlreadyRegisteredException;
use PrecisionSoft\Symfony\Phpunit\Exception\MockNotFoundException;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\CircularAlphaMock;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\SecondMockDto;

/**
 * @internal
 */
final class MockContainerEdgeCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

        $this->expectException(MockAlreadyRegisteredException::class);
        $this->expectExceptionMessage(\sprintf('mock dto already registered for class `%s`', SecondMockDto::class));

        $this->mockContainer->registerMockDto($mockDto);
    }

    public function testGetMockThrowsExceptionWhenNotRegistered(): void
    {
        $this->expectException(MockNotFoundException::class);
        $this->expectExceptionMessage(\sprintf('no mock dto found for class `%s`', SecondMockDto::class));

        $this->mockContainer->getMock(SecondMockDto::class);
    }

    public function testRegisterMockThrowsExceptionOnDuplicate(): void
    {
        $this->mockContainer->registerMockDto(new MockDto(SecondMockDto::class));
        $mockInterface = $this->mockContainer->getMock(SecondMockDto::class);

        $this->expectException(MockAlreadyRegisteredException::class);
        $this->expectExceptionMessage(\sprintf('mock already registered for class `%s`', SecondMockDto::class));

        $this->mockContainer->registerMock(SecondMockDto::class, $mockInterface);
    }

    public function testRegisterMockDirectlyAndRetrieve(): void
    {
        $externalMockInterface = Mockery::mock(SecondMockDto::class);
        $this->mockContainer->registerMock(SecondMockDto::class, $externalMockInterface);

        $retrieved = $this->mockContainer->getMock(SecondMockDto::class);

        static::assertSame($externalMockInterface, $retrieved);
    }

    public function testGetMockReturnsSameInstanceOnSubsequentCalls(): void
    {
        $this->mockContainer->registerMockDto(new MockDto(SecondMockDto::class));

        $firstMockInterface = $this->mockContainer->getMock(SecondMockDto::class);
        $secondMockInterface = $this->mockContainer->getMock(SecondMockDto::class);

        static::assertSame($firstMockInterface, $secondMockInterface);
    }

    public function testOnCreateCallbackIsInvoked(): void
    {
        $callbackInvoked = false;
        $mockDto = new MockDto(
            SecondMockDto::class,
            null,
            false,
            static function (MockInterface $mockInterface, MockContainer $mockContainer) use (&$callbackInvoked): void {
                $callbackInvoked = true;
            },
        );

        $this->mockContainer->registerMockDto($mockDto);
        $this->mockContainer->getMock(SecondMockDto::class);

        static::assertTrue($callbackInvoked);
    }

    public function testOnCreateCallbackReceivesMockAndContainer(): void
    {
        $receivedMockInterface = null;
        $receivedMockContainer = null;

        $mockDto = new MockDto(
            SecondMockDto::class,
            null,
            false,
            static function (MockInterface $mockInterface, MockContainer $mockContainer) use (&$receivedMockInterface, &$receivedMockContainer): void {
                $receivedMockInterface = $mockInterface;
                $receivedMockContainer = $mockContainer;
            },
        );

        $this->mockContainer->registerMockDto($mockDto);
        $createdMockInterface = $this->mockContainer->getMock(SecondMockDto::class);

        static::assertSame($createdMockInterface, $receivedMockInterface);
        static::assertSame($this->mockContainer, $receivedMockContainer);
    }

    public function testPartialMockIsCreated(): void
    {
        $this->mockContainer->registerMockDto(new MockDto(SecondMockDto::class, null, true));

        $mockInterface = $this->mockContainer->getMock(SecondMockDto::class);

        static::assertInstanceOf(MockInterface::class, $mockInterface);
        static::assertInstanceOf(SecondMockDto::class, $mockInterface);
    }

    public function testRegisterMockDtoReturnsSelf(): void
    {
        $mockDto = new MockDto(SecondMockDto::class);

        $result = $this->mockContainer->registerMockDto($mockDto);

        static::assertSame($this->mockContainer, $result);
    }

    public function testRegisterMockReturnsSelf(): void
    {
        $externalMockInterface = Mockery::mock(SecondMockDto::class);

        $result = $this->mockContainer->registerMock(SecondMockDto::class, $externalMockInterface);

        static::assertSame($this->mockContainer, $result);
    }

    public function testCircularDependencyThrowsException(): void
    {
        $this->mockContainer->registerMockDto(CircularAlphaMock::getMockDto());

        $this->expectException(CircularDependencyException::class);
        $this->expectExceptionMessage(
            \sprintf('circular dependency detected for class `%s`', CircularAlphaMock::class),
        );

        $this->mockContainer->getMock(CircularAlphaMock::class);
    }
}
