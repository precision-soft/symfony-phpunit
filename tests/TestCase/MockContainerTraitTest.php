<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\TestCase;

use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use PrecisionSoft\Symfony\Phpunit\Exception\MockContainerNotInitializedException;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\SecondMockDto;
use PrecisionSoft\Symfony\Phpunit\TestCase\Trait\MockContainerTrait;

/**
 * @internal
 */
final class MockContainerTraitTest extends TestCase
{
    public function testGetThrowsExceptionWhenMockContainerIsNull(): void
    {
        $testCase = new class extends TestCase {
            use MockContainerTrait {
                get as public;
            }
        };

        $this->expectException(MockContainerNotInitializedException::class);
        $this->expectExceptionMessage('mock container is not initialized');

        $testCase->get(SecondMockDto::class);
    }

    public function testGetReturnsMockAfterRegisterMockDto(): void
    {
        $testCase = new class extends TestCase {
            use MockContainerTrait {
                get as public;
                registerMockDto as public;
            }
        };

        $testCase->registerMockDto(new MockDto(SecondMockDto::class));

        $mockInterface = $testCase->get(SecondMockDto::class);

        static::assertInstanceOf(MockInterface::class, $mockInterface);
        static::assertInstanceOf(SecondMockDto::class, $mockInterface);
    }

    public function testTearDownClosesMockContainerGracefullyWhenNull(): void
    {
        $testCase = new class ('testNothing') extends TestCase {
            use MockContainerTrait {
                tearDown as public traitTearDown;
            }

            public function testNothing(): void
            {
                static::assertTrue(true);
            }
        };

        $testCase->traitTearDown();

        static::assertTrue(true);
    }

    public function testRegisterMockDtoInitializesContainerOnFirstCall(): void
    {
        $testCase = new class extends TestCase {
            use MockContainerTrait {
                get as public;
                registerMockDto as public;
            }
        };

        $result = $testCase->registerMockDto(new MockDto(SecondMockDto::class));

        static::assertSame($testCase, $result);

        $mockInterface = $testCase->get(SecondMockDto::class);
        static::assertInstanceOf(MockInterface::class, $mockInterface);
    }

    public function testRegisterMockDtoChaining(): void
    {
        $testCase = new class extends TestCase {
            use MockContainerTrait {
                get as public;
                registerMockDto as public;
            }
        };

        $result = $testCase
            ->registerMockDto(new MockDto(SecondMockDto::class));

        static::assertSame($testCase, $result);
    }
}
