<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\TestCase;

use Mockery\MockInterface;
use PrecisionSoft\Symfony\Phpunit\Contract\MockDtoInterface;
use PrecisionSoft\Symfony\Phpunit\Mock\EventDispatcherInterfaceMock;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\SecondMockDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\TestKernel;
use PrecisionSoft\Symfony\Phpunit\TestCase\AbstractKernelTestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class AbstractKernelTestCaseTest extends AbstractKernelTestCase
{
    public static function getMockDto(): MockDto
    {
        return new MockDto(SecondMockDto::class);
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
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

    public function testSetUpInitializesMockContainer(): void
    {
        $mockInterface = $this->get(SecondMockDto::class);

        static::assertInstanceOf(MockInterface::class, $mockInterface);
    }

    public function testGetReturnsSameMockInstance(): void
    {
        $firstMockInterface = $this->get(SecondMockDto::class);
        $secondMockInterface = $this->get(SecondMockDto::class);

        static::assertSame($firstMockInterface, $secondMockInterface);
    }

    public function testImplementsMockDtoInterface(): void
    {
        static::assertInstanceOf(
            MockDtoInterface::class,
            $this,
        );
    }
}
