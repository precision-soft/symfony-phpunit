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
use stdClass;

/**
 * @internal
 */
final class MockContainerScopedOverrideTest extends TestCase
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
}
