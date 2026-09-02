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
use PrecisionSoft\Symfony\Phpunit\Exception\Exception;
use PrecisionSoft\Symfony\Phpunit\Exception\MockAlreadyRegisteredException;
use PrecisionSoft\Symfony\Phpunit\Exception\MockClassMismatchException;
use PrecisionSoft\Symfony\Phpunit\Exception\MockNotFoundException;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\CircularAlphaMock;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\ConstructorTrackingDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\ExtendedSecondMockDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\FinalDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\RecordingMockContainer;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\SecondMockDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\TripleCircularAlphaMock;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\TripleCircularBetaMock;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\UnrelatedDto;

/**
 * @internal
 */
final class MockContainerEdgeCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private MockContainer $mockContainer;

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

    public function testRegisterMockDtoThrowsExceptionWhenMockAlreadyRegistered(): void
    {
        $externalMockInterface = Mockery::mock(SecondMockDto::class);
        $this->mockContainer->registerMock(SecondMockDto::class, $externalMockInterface);

        $this->expectException(MockAlreadyRegisteredException::class);
        $this->expectExceptionMessage(\sprintf('mock already registered for class `%s`', SecondMockDto::class));

        $this->mockContainer->registerMockDto(new MockDto(SecondMockDto::class));
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

    public function testHasMockReturnsFalseWhenNothingRegistered(): void
    {
        static::assertFalse($this->mockContainer->hasMock(SecondMockDto::class));
    }

    public function testHasMockReturnsTrueAfterRegisterMockDto(): void
    {
        $this->mockContainer->registerMockDto(new MockDto(SecondMockDto::class));

        static::assertTrue($this->mockContainer->hasMock(SecondMockDto::class));
    }

    public function testHasMockReturnsTrueAfterRegisterMock(): void
    {
        $externalMockInterface = Mockery::mock(SecondMockDto::class);
        $this->mockContainer->registerMock(SecondMockDto::class, $externalMockInterface);

        static::assertTrue($this->mockContainer->hasMock(SecondMockDto::class));
    }

    public function testHasMockReturnsFalseAfterClose(): void
    {
        $this->mockContainer->registerMockDto(new MockDto(SecondMockDto::class));
        $this->mockContainer->getMock(SecondMockDto::class);

        $this->mockContainer->close();

        static::assertFalse($this->mockContainer->hasMock(SecondMockDto::class));
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

    public function testCloseResetsCreatingGuardAfterCircularDependency(): void
    {
        $this->mockContainer->registerMockDto(CircularAlphaMock::getMockDto());

        try {
            $this->mockContainer->getMock(CircularAlphaMock::class);
        } catch (CircularDependencyException) {
        }

        $this->mockContainer->close();

        $this->mockContainer->registerMockDto(new MockDto(SecondMockDto::class));
        $mockInterface = $this->mockContainer->getMock(SecondMockDto::class);

        static::assertInstanceOf(MockInterface::class, $mockInterface);
    }

    public function testOnCreateExceptionCleansMockFromRegistry(): void
    {
        $callCount = 0;

        $mockDto = new MockDto(
            SecondMockDto::class,
            null,
            false,
            static function (MockInterface $mockInterface, MockContainer $mockContainer) use (&$callCount): void {
                ++$callCount;

                if (1 === $callCount) {
                    throw new Exception('onCreate failure');
                }
            },
        );

        $this->mockContainer->registerMockDto($mockDto);

        try {
            $this->mockContainer->getMock(SecondMockDto::class);
            static::fail('Expected Exception was not thrown');
        } catch (Exception $exception) {
            static::assertSame('onCreate failure', $exception->getMessage());
        }

        $this->mockContainer->registerMockDto($mockDto);
        $mockInterface = $this->mockContainer->getMock(SecondMockDto::class);

        static::assertInstanceOf(MockInterface::class, $mockInterface);
        static::assertSame(2, $callCount);
    }

    public function testCreateMockAndGetOrCreateMockAreOverridableExtensionPoints(): void
    {
        $recordingMockContainer = new RecordingMockContainer();

        $recordingMockContainer->registerMockDto(
            new MockDto(ConstructorTrackingDto::class, [new MockDto(SecondMockDto::class)]),
        );

        $recordingMockContainer->getMock(ConstructorTrackingDto::class);

        static::assertSame(
            [ConstructorTrackingDto::class, SecondMockDto::class],
            $recordingMockContainer->getCreatedClassList(),
        );
        static::assertSame([SecondMockDto::class], $recordingMockContainer->getResolvedDependencyClassList());

        $recordingMockContainer->close();
    }

    public function testRegisterMockRejectsAMockOfAForeignClass(): void
    {
        $unrelatedMockInterface = Mockery::mock(UnrelatedDto::class);

        $this->expectException(MockClassMismatchException::class);
        $this->expectExceptionMessage(
            \sprintf('mock is not an instance of class `%s`', SecondMockDto::class),
        );

        $this->mockContainer->registerMock(SecondMockDto::class, $unrelatedMockInterface);
    }

    public function testRegisterMockRejectionCarriesBothClassesInItsContext(): void
    {
        $unrelatedMockInterface = Mockery::mock(UnrelatedDto::class);

        try {
            $this->mockContainer->registerMock(SecondMockDto::class, $unrelatedMockInterface);

            static::fail('registerMock() must reject a mock of a foreign class');
        } catch (MockClassMismatchException $mockClassMismatchException) {
            static::assertSame(
                ['expectedClass' => SecondMockDto::class, 'actualClass' => $unrelatedMockInterface::class],
                $mockClassMismatchException->getContext(),
            );
            static::assertSame(0, $mockClassMismatchException->getCode());
        }
    }

    public function testRegisterMockRejectsAProxiedPartialOfAFinalClass(): void
    {
        $finalMockInterface = Mockery::mock(new FinalDto());

        $this->expectException(MockClassMismatchException::class);
        $this->expectExceptionMessage(\sprintf('mock is not an instance of class `%s`', FinalDto::class));

        $this->mockContainer->registerMock(FinalDto::class, $finalMockInterface);
    }

    public function testRegisterMockRejectionLeavesTheContainerUnchanged(): void
    {
        $unrelatedMockInterface = Mockery::mock(UnrelatedDto::class);

        try {
            $this->mockContainer->registerMock(SecondMockDto::class, $unrelatedMockInterface);
        } catch (MockClassMismatchException) {
        }

        static::assertFalse($this->mockContainer->hasMock(SecondMockDto::class));
    }

    public function testRegisterMockAcceptsAMockOfASubclass(): void
    {
        $extendedMockInterface = Mockery::mock(ExtendedSecondMockDto::class);

        $this->mockContainer->registerMock(SecondMockDto::class, $extendedMockInterface);

        static::assertSame($extendedMockInterface, $this->mockContainer->getMock(SecondMockDto::class));
    }

    public function testMockIsNotCreatedUntilFirstGet(): void
    {
        $onCreateCallCount = 0;

        $mockDto = new MockDto(
            SecondMockDto::class,
            null,
            false,
            static function () use (&$onCreateCallCount): void {
                ++$onCreateCallCount;
            },
        );

        $this->mockContainer->registerMockDto($mockDto);

        static::assertSame(0, $onCreateCallCount);

        $this->mockContainer->getMock(SecondMockDto::class);

        static::assertSame(1, $onCreateCallCount);

        $this->mockContainer->getMock(SecondMockDto::class);

        static::assertSame(1, $onCreateCallCount);
    }

    public function testConstructDependenciesAreNotResolvedUntilFirstGet(): void
    {
        $this->mockContainer->registerMockDto(
            new MockDto(ConstructorTrackingDto::class, [new MockDto(SecondMockDto::class)]),
        );

        static::assertFalse($this->mockContainer->hasMock(SecondMockDto::class));

        $this->mockContainer->getMock(ConstructorTrackingDto::class);

        static::assertTrue($this->mockContainer->hasMock(SecondMockDto::class));
    }

    public function testCircularDependencyOfLengthThreeThrowsException(): void
    {
        $this->mockContainer->registerMockDto(TripleCircularAlphaMock::getMockDto());

        $this->expectException(CircularDependencyException::class);
        $this->expectExceptionMessage(
            \sprintf('circular dependency detected for class `%s`', TripleCircularAlphaMock::class),
        );

        $this->mockContainer->getMock(TripleCircularAlphaMock::class);
    }

    public function testCircularDependencyGuardIsClearedForEveryLinkOfTheChain(): void
    {
        $this->mockContainer->registerMockDto(TripleCircularAlphaMock::getMockDto());

        try {
            $this->mockContainer->getMock(TripleCircularAlphaMock::class);
        } catch (CircularDependencyException) {
        }

        $this->mockContainer->registerMockDto(TripleCircularBetaMock::getMockDto());

        $this->expectException(CircularDependencyException::class);
        $this->expectExceptionMessage(
            \sprintf('circular dependency detected for class `%s`', TripleCircularBetaMock::class),
        );

        $this->mockContainer->getMock(TripleCircularBetaMock::class);
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
