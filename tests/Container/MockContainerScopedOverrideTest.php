<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Container;

use ArrayObject;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\Exception\Exception;
use PrecisionSoft\Symfony\Phpunit\Exception\MockClassMismatchException;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\ConstructorTrackingDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\SecondMockDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\ThirdMockDto;
use stdClass;

/**
 * @internal
 */
final class MockContainerScopedOverrideTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private MockContainer $mockContainer;

    public function testOverrideIsVisibleOnlyInsideTheCallback(): void
    {
        $this->mockContainer->registerMockDto(new MockDto(stdClass::class));
        $originalMockInterface = $this->mockContainer->getMock(stdClass::class);
        $overrideMockInterface = Mockery::mock(stdClass::class);

        $callbackResult = $this->mockContainer->withMock(
            stdClass::class,
            $overrideMockInterface,
            static function (
                MockInterface $scopedMockInterface,
                MockContainer $scopedMockContainer,
            ) use ($overrideMockInterface): string {
                static::assertSame($overrideMockInterface, $scopedMockInterface);
                static::assertSame($overrideMockInterface, $scopedMockContainer->getMock(stdClass::class));

                return 'callback result';
            },
        );

        static::assertSame('callback result', $callbackResult);
        static::assertSame($originalMockInterface, $this->mockContainer->getMock(stdClass::class));
    }

    public function testOverrideIsRestoredWhenTheCallbackThrows(): void
    {
        $this->mockContainer->registerMockDto(new MockDto(stdClass::class));
        $originalMockInterface = $this->mockContainer->getMock(stdClass::class);
        $caughtException = null;

        try {
            $this->mockContainer->withMock(
                stdClass::class,
                Mockery::mock(stdClass::class),
                static fn(): never => throw new Exception('callback failed'),
            );
        } catch (Exception $exception) {
            $caughtException = $exception;
        }

        static::assertInstanceOf(Exception::class, $caughtException);
        static::assertSame('callback failed', $caughtException->getMessage());
        static::assertSame($originalMockInterface, $this->mockContainer->getMock(stdClass::class));
    }

    public function testOverrideLeavesNoRegistrationWhenThereWasNone(): void
    {
        $overrideMockInterface = Mockery::mock(stdClass::class);

        static::assertFalse($this->mockContainer->hasMock(stdClass::class));

        $this->mockContainer->withMock(
            stdClass::class,
            $overrideMockInterface,
            static function (MockInterface $scopedMockInterface, MockContainer $scopedMockContainer) use (
                $overrideMockInterface,
            ): void {
                static::assertTrue($scopedMockContainer->hasMock(stdClass::class));
                static::assertSame($overrideMockInterface, $scopedMockInterface);
            },
        );

        static::assertFalse($this->mockContainer->hasMock(stdClass::class));
    }

    public function testOverrideRestoresAnUnmaterializedMockDto(): void
    {
        $this->mockContainer->registerMockDto(new MockDto(stdClass::class));
        $overrideMockInterface = Mockery::mock(stdClass::class);

        $this->mockContainer->withMock(
            stdClass::class,
            $overrideMockInterface,
            static function (MockInterface $scopedMockInterface) use ($overrideMockInterface): void {
                static::assertSame($overrideMockInterface, $scopedMockInterface);
            },
        );

        static::assertTrue($this->mockContainer->hasMock(stdClass::class));
        static::assertNotSame($overrideMockInterface, $this->mockContainer->getMock(stdClass::class));
    }

    public function testOverrideRejectsTheWrongClass(): void
    {
        $this->expectException(MockClassMismatchException::class);
        $this->expectExceptionMessage(\sprintf('mock is not an instance of class `%s`', stdClass::class));

        $this->mockContainer->withMock(
            stdClass::class,
            Mockery::mock(ArrayObject::class),
            static fn(): null => null,
        );
    }

    public function testTheScopeLeavesTheRegistryExactlyAsItFoundIt(): void
    {
        $this->mockContainer->registerMockDto(
            new MockDto(ConstructorTrackingDto::class, [ThirdMockDto::class], true),
        );
        $overrideMockInterface = Mockery::mock(SecondMockDto::class);

        $scopedConstructorTrackingDto = $this->mockContainer->withMock(
            SecondMockDto::class,
            $overrideMockInterface,
            static function (
                MockInterface $scopedMockInterface,
                MockContainer $scopedMockContainer,
            ): ConstructorTrackingDto {
                $scopedMockContainer->registerMockDto(new MockDto(stdClass::class));

                return $scopedMockContainer->getMock(ConstructorTrackingDto::class);
            },
        );

        static::assertSame($overrideMockInterface, $scopedConstructorTrackingDto->secondMockDto);
        static::assertFalse($this->mockContainer->hasMock(stdClass::class));
        static::assertFalse($this->mockContainer->hasMock(SecondMockDto::class));
        static::assertTrue($this->mockContainer->hasMock(ConstructorTrackingDto::class));

        $constructorTrackingDto = $this->mockContainer->getMock(ConstructorTrackingDto::class);

        static::assertNotSame($scopedConstructorTrackingDto, $constructorTrackingDto);
        static::assertNotSame($overrideMockInterface, $constructorTrackingDto->secondMockDto);
        static::assertSame($this->mockContainer->getMock(SecondMockDto::class), $constructorTrackingDto->secondMockDto);
    }

    public function testCloseInsideTheScopeDoesNotResurrectAnything(): void
    {
        $this->mockContainer->registerMockDto(new MockDto(stdClass::class));
        $this->mockContainer->getMock(stdClass::class);

        $this->mockContainer->withMock(
            stdClass::class,
            Mockery::mock(stdClass::class),
            static function (MockInterface $scopedMockInterface, MockContainer $scopedMockContainer): void {
                $scopedMockContainer->close();
            },
        );

        static::assertFalse($this->mockContainer->hasMock(stdClass::class));
    }

    public function testWithMockInsideOnCreateRestoresTheMockBeingCreated(): void
    {
        $seenInsideTheScope = null;

        $this->mockContainer->registerMockDto(
            new MockDto(
                SecondMockDto::class,
                null,
                false,
                static function (MockInterface $mockInterface, MockContainer $mockContainer) use (
                    &$seenInsideTheScope,
                ): void {
                    $mockContainer->withMock(
                        SecondMockDto::class,
                        Mockery::mock(SecondMockDto::class),
                        static function (
                            MockInterface $scopedMockInterface,
                            MockContainer $scopedMockContainer,
                        ) use (&$seenInsideTheScope): void {
                            $seenInsideTheScope = $scopedMockContainer->getMock(SecondMockDto::class);
                        },
                    );
                },
            ),
        );

        $secondMockDto = $this->mockContainer->getMock(SecondMockDto::class);

        static::assertInstanceOf(MockInterface::class, $seenInsideTheScope);
        static::assertNotSame($secondMockDto, $seenInsideTheScope);
        static::assertSame($secondMockDto, $this->mockContainer->getMock(SecondMockDto::class));
    }

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
}
