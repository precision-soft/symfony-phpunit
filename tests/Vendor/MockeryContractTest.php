<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Vendor;

use Doctrine\ORM\EntityManagerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Exception\BadMethodCallException;
use Mockery\HigherOrderMessage;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\ConstructorTrackingDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\SecondMockDto;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

/** @internal */
final class MockeryContractTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testMockOfAClassIsBothAMockInterfaceAndAnInstanceOfThatClass(): void
    {
        $mockInterface = Mockery::mock(SecondMockDto::class);

        static::assertInstanceOf(MockInterface::class, $mockInterface);
        static::assertInstanceOf(SecondMockDto::class, $mockInterface);
    }

    public function testMockOfAnInterfaceImplementsThatInterface(): void
    {
        $mockInterface = Mockery::mock(SluggerInterface::class);

        static::assertInstanceOf(MockInterface::class, $mockInterface);
        static::assertInstanceOf(SluggerInterface::class, $mockInterface);
    }

    public function testConstructorArgumentsReachTheOriginalConstructor(): void
    {
        $secondMockDto = new SecondMockDto();

        /** @var ConstructorTrackingDto&MockInterface $constructorTrackingDto */
        $constructorTrackingDto = Mockery::mock(ConstructorTrackingDto::class, [$secondMockDto]);

        static::assertTrue($constructorTrackingDto->constructorCalled);
        static::assertSame($secondMockDto, $constructorTrackingDto->secondMockDto);
    }

    public function testShouldReceiveExposesTheExpectationApiThisPackageUses(): void
    {
        /** @var EntityManagerInterface&MockInterface $entityManagerInterface */
        $entityManagerInterface = Mockery::mock(EntityManagerInterface::class);

        $entityManagerInterface->shouldReceive('find')
            ->byDefault()
            ->andReturnNull();

        static::assertNull($entityManagerInterface->find(SecondMockDto::class, 1));

        $entityManagerInterface->shouldReceive('contains')
            ->andReturn(true);

        static::assertTrue($entityManagerInterface->contains(new SecondMockDto()));

        $secondMockDto = new SecondMockDto();

        $entityManagerInterface->shouldReceive('find')
            ->andReturnUsing(static function (string $className, mixed $identifier) use ($secondMockDto): ?object {
                return SecondMockDto::class === $className ? $secondMockDto : null;
            });

        static::assertSame($secondMockDto, $entityManagerInterface->find(SecondMockDto::class, 1));
        static::assertNull($entityManagerInterface->find(ConstructorTrackingDto::class, 1));
    }

    public function testHigherOrderMessageIsStillPartOfTheShouldReceiveReturnType(): void
    {
        static::assertTrue(\class_exists(HigherOrderMessage::class));
    }

    public function testByDefaultExpectationIsOverriddenByALaterExpectation(): void
    {
        /** @var MockInterface&SluggerInterface $sluggerInterface */
        $sluggerInterface = Mockery::mock(SluggerInterface::class);

        $sluggerInterface->shouldReceive('slug')
            ->byDefault()
            ->andReturn(new UnicodeString('default'));

        $overriddenUnicodeString = new UnicodeString('overridden');

        $sluggerInterface->shouldReceive('slug')
            ->andReturn($overriddenUnicodeString);

        static::assertSame($overriddenUnicodeString, $sluggerInterface->slug('anything'));
    }

    public function testMakePartialDefersUnstubbedMethodsToTheOriginalImplementation(): void
    {
        /** @var ConstructorTrackingDto&MockInterface $constructorTrackingDto */
        $constructorTrackingDto = Mockery::mock(ConstructorTrackingDto::class, [new SecondMockDto()]);
        $constructorTrackingDto->makePartial();

        static::assertSame('original implementation', $constructorTrackingDto->describe());
    }

    public function testAnUnexpectedCallIsRecordedOnTheMockAndCanBeDismissed(): void
    {
        /** @var ConstructorTrackingDto&MockInterface $constructorTrackingDto */
        $constructorTrackingDto = Mockery::mock(ConstructorTrackingDto::class, [new SecondMockDto()]);

        try {
            $constructorTrackingDto->describe();

            static::fail('a full mock must reject a method call that carries no expectation');
        } catch (BadMethodCallException $badMethodCallException) {
            static::assertStringContainsString('describe', $badMethodCallException->getMessage());

            static::assertFalse($badMethodCallException->dismissed());

            $badMethodCallException->dismiss();

            static::assertTrue($badMethodCallException->dismissed());
        }
    }
}
