<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\TestCase;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Exception\InvalidCountException;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\Exception\MockContainerNotInitializedException;
use PrecisionSoft\Symfony\Phpunit\Exception\MockNotFoundException;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\ConstructorTrackingDto;
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

        $this->addToAssertionCount(1);
    }

    public function testTearDownClosesTheContainerAndVerifiesItsExpectations(): void
    {
        $mockContainerTraitTearDownTestCase = new MockContainerTraitTearDownTestCase('testNothing');

        $mockContainerTraitTearDownTestCase->registerMockDto(
            new MockDto(
                ConstructorTrackingDto::class,
                null,
                false,
                static function (MockInterface $mockInterface): void {
                    $mockInterface->shouldReceive('describe')
                        ->once();
                },
            ),
        );

        $mockContainerTraitTearDownTestCase->get(ConstructorTrackingDto::class);

        try {
            $mockContainerTraitTearDownTestCase->traitTearDown();

            static::fail('tearDown() must close the container, which verifies the expectations set on its mocks');
        } catch (InvalidCountException $invalidCountException) {
            static::assertStringContainsString('describe', $invalidCountException->getMessage());
        }
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

    public function testWithMockScopesTheOverrideAndRestoresThePreviousMock(): void
    {
        $mockContainerTraitTestCase = new MockContainerTraitTestCase();

        $mockContainerTraitTestCase->registerMockDto(new MockDto(SecondMockDto::class));
        $originalMockInterface = $mockContainerTraitTestCase->get(SecondMockDto::class);
        $overrideMockInterface = Mockery::mock(SecondMockDto::class);

        $callbackResult = $mockContainerTraitTestCase->withMock(
            SecondMockDto::class,
            $overrideMockInterface,
            static function (
                MockInterface $scopedMockInterface,
                MockContainer $scopedMockContainer,
            ) use ($overrideMockInterface): string {
                static::assertSame($overrideMockInterface, $scopedMockInterface);
                static::assertSame($overrideMockInterface, $scopedMockContainer->getMock(SecondMockDto::class));

                return 'callback result';
            },
        );

        static::assertSame('callback result', $callbackResult);
        static::assertSame($originalMockInterface, $mockContainerTraitTestCase->get(SecondMockDto::class));
    }

    public function testWithMockInitializesContainerOnFirstCall(): void
    {
        $mockContainerTraitTestCase = new MockContainerTraitTestCase();

        $overrideMockInterface = Mockery::mock(SecondMockDto::class);

        $scopedMockInterface = $mockContainerTraitTestCase->withMock(
            SecondMockDto::class,
            $overrideMockInterface,
            static fn(MockInterface $mockInterface): MockInterface => $mockInterface,
        );

        static::assertSame($overrideMockInterface, $scopedMockInterface);

        $this->expectException(MockNotFoundException::class);

        $mockContainerTraitTestCase->get(SecondMockDto::class);
    }
}
