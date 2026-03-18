<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\TestCase;

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
        $mock = $this->get(SecondMockDto::class);

        static::assertInstanceOf(MockInterface::class, $mock);
        static::assertInstanceOf(SecondMockDto::class, $mock);
    }

    public function testRegisterMockDtoAddsAdditionalMock(): void
    {
        $this->registerMockDto(EventDispatcherInterfaceMock::getMockDto());

        $mock = $this->get(EventDispatcherInterface::class);

        static::assertInstanceOf(MockInterface::class, $mock);
        static::assertInstanceOf(EventDispatcherInterface::class, $mock);
    }

    public function testRegisterMockDtoReturnsSelf(): void
    {
        $result = $this->registerMockDto(EventDispatcherInterfaceMock::getMockDto());

        static::assertSame($this, $result);
    }
}
