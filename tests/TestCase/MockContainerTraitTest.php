<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\TestCase;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use PrecisionSoft\Symfony\Phpunit\Exception\MockContainerNotInitializedException;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\MockContainerTraitTearDownTestCase;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\MockContainerTraitTestCase;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\SecondMockDto;

/**
 * @internal
 */
final class MockContainerTraitTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetThrowsExceptionWhenMockContainerIsNull(): void
    {
        $mockContainerTraitTestCase = new MockContainerTraitTestCase();

        $this->expectException(MockContainerNotInitializedException::class);
        $this->expectExceptionMessage('mock container is not initialized');

        $mockContainerTraitTestCase->get(SecondMockDto::class);
    }

    public function testGetReturnsMockAfterRegisterMockDto(): void
    {
        $mockContainerTraitTestCase = new MockContainerTraitTestCase();

        $mockContainerTraitTestCase->registerMockDto(new MockDto(SecondMockDto::class));

        $mockInterface = $mockContainerTraitTestCase->get(SecondMockDto::class);

        static::assertInstanceOf(MockInterface::class, $mockInterface);
        static::assertInstanceOf(SecondMockDto::class, $mockInterface);
    }

    public function testTearDownClosesMockContainerGracefullyWhenNull(): void
    {
        $mockContainerTraitTearDownTestCase = new MockContainerTraitTearDownTestCase('testNothing');

        $mockContainerTraitTearDownTestCase->traitTearDown();

        static::assertTrue(true);
    }

    public function testRegisterMockDtoInitializesContainerOnFirstCall(): void
    {
        $mockContainerTraitTestCase = new MockContainerTraitTestCase();

        $result = $mockContainerTraitTestCase->registerMockDto(new MockDto(SecondMockDto::class));

        static::assertSame($mockContainerTraitTestCase, $result);

        $mockInterface = $mockContainerTraitTestCase->get(SecondMockDto::class);
        static::assertInstanceOf(MockInterface::class, $mockInterface);
    }

    public function testRegisterMockDtoChaining(): void
    {
        $mockContainerTraitTestCase = new MockContainerTraitTestCase();

        $result = $mockContainerTraitTestCase
            ->registerMockDto(new MockDto(SecondMockDto::class));

        static::assertSame($mockContainerTraitTestCase, $result);
    }

    public function testRegisterMockRegistersPreBuiltMock(): void
    {
        $mockContainerTraitTestCase = new MockContainerTraitTestCase();

        $externalMockInterface = Mockery::mock(SecondMockDto::class);
        $mockContainerTraitTestCase->registerMock(SecondMockDto::class, $externalMockInterface);

        $retrievedMockInterface = $mockContainerTraitTestCase->get(SecondMockDto::class);

        static::assertSame($externalMockInterface, $retrievedMockInterface);
    }

    public function testRegisterMockReturnsSelf(): void
    {
        $mockContainerTraitTestCase = new MockContainerTraitTestCase();

        $externalMockInterface = Mockery::mock(SecondMockDto::class);

        $result = $mockContainerTraitTestCase->registerMock(SecondMockDto::class, $externalMockInterface);

        static::assertSame($mockContainerTraitTestCase, $result);
    }

    public function testRegisterMockInitializesContainerOnFirstCall(): void
    {
        $mockContainerTraitTestCase = new MockContainerTraitTestCase();

        $externalMockInterface = Mockery::mock(SecondMockDto::class);
        $mockContainerTraitTestCase->registerMock(SecondMockDto::class, $externalMockInterface);

        $retrievedMockInterface = $mockContainerTraitTestCase->get(SecondMockDto::class);

        static::assertSame($externalMockInterface, $retrievedMockInterface);
    }
}
