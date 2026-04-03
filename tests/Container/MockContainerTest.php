<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Container;

use Doctrine\Persistence\ManagerRegistry;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\Mock\EventDispatcherInterfaceMock;
use PrecisionSoft\Symfony\Phpunit\Mock\ManagerRegistryMock;
use PrecisionSoft\Symfony\Phpunit\Mock\SluggerInterfaceMock;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\DeepNestedServiceDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\FirstMockDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\MixedConstructorDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\NullableConstructorDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\ScalarConstructorDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\SecondMockDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\ThirdMockDtoInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class MockContainerTest extends TestCase
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

    public function testPartialMockWithConstructDependenciesResolvesCorrectly(): void
    {
        $mockDto = new MockDto(
            FirstMockDto::class,
            [
                new MockDto(SecondMockDto::class),
                EventDispatcherInterfaceMock::class,
                ManagerRegistryMock::class,
                SluggerInterfaceMock::class,
            ],
            true,
        );

        $this->mockContainer->registerMockDto($mockDto);

        /** @var FirstMockDto $firstMockDto */
        $firstMockDto = $this->mockContainer->getMock(FirstMockDto::class);

        static::assertInstanceOf(MockInterface::class, $firstMockDto);
        static::assertInstanceOf(FirstMockDto::class, $firstMockDto);
        static::assertInstanceOf(EventDispatcherInterface::class, $firstMockDto->getEventDispatcher());
        static::assertInstanceOf(ManagerRegistry::class, $firstMockDto->getManagerRegistry());
        static::assertInstanceOf(SluggerInterface::class, $firstMockDto->getSlugger());
    }

    public function testConstructDependencyAsMockDtoInstance(): void
    {
        $mockDto = new MockDto(
            FirstMockDto::class,
            [
                new MockDto(SecondMockDto::class),
                new MockDto(EventDispatcherInterface::class),
                new MockDto(ManagerRegistry::class),
                new MockDto(SluggerInterface::class),
            ],
            true,
        );

        $this->mockContainer->registerMockDto($mockDto);

        /** @var FirstMockDto $firstMockDto */
        $firstMockDto = $this->mockContainer->getMock(FirstMockDto::class);

        static::assertInstanceOf(SecondMockDto::class, $firstMockDto->getSecondMockDto());
        static::assertInstanceOf(EventDispatcherInterface::class, $firstMockDto->getEventDispatcher());
        static::assertInstanceOf(ManagerRegistry::class, $firstMockDto->getManagerRegistry());
        static::assertInstanceOf(SluggerInterface::class, $firstMockDto->getSlugger());
    }

    public function testConstructDependencyAsClassStringMockDtoInterface(): void
    {
        $mockDto = new MockDto(
            FirstMockDto::class,
            [
                new MockDto(SecondMockDto::class),
                EventDispatcherInterfaceMock::class,
                ManagerRegistryMock::class,
                SluggerInterfaceMock::class,
            ],
            true,
        );

        $this->mockContainer->registerMockDto($mockDto);

        /** @var FirstMockDto $firstMockDto */
        $firstMockDto = $this->mockContainer->getMock(FirstMockDto::class);

        static::assertInstanceOf(MockInterface::class, $this->mockContainer->getMock(EventDispatcherInterface::class));
        static::assertInstanceOf(MockInterface::class, $this->mockContainer->getMock(ManagerRegistry::class));
        static::assertInstanceOf(MockInterface::class, $this->mockContainer->getMock(SluggerInterface::class));
        static::assertInstanceOf(EventDispatcherInterface::class, $firstMockDto->getEventDispatcher());
        static::assertInstanceOf(ManagerRegistry::class, $firstMockDto->getManagerRegistry());
        static::assertInstanceOf(SluggerInterface::class, $firstMockDto->getSlugger());
    }

    public function testConstructDependencyAsScalarValues(): void
    {
        $mockDto = new MockDto(
            ScalarConstructorDto::class,
            [
                'test-name',
                42,
                true,
            ],
            true,
        );

        $this->mockContainer->registerMockDto($mockDto);

        /** @var ScalarConstructorDto $scalarConstructorDto */
        $scalarConstructorDto = $this->mockContainer->getMock(ScalarConstructorDto::class);

        static::assertSame('test-name', $scalarConstructorDto->getName());
        static::assertSame(42, $scalarConstructorDto->getCount());
        static::assertTrue($scalarConstructorDto->getActive());
    }

    public function testConstructDependencyAsMockDtoInterfaceInstance(): void
    {
        $thirdMockDtoInterface = new ThirdMockDtoInterface();

        $mockDto = new MockDto(
            MixedConstructorDto::class,
            [
                $thirdMockDtoInterface,
                EventDispatcherInterfaceMock::class,
                'instance-name',
                7,
            ],
            true,
        );

        $this->mockContainer->registerMockDto($mockDto);

        /** @var MixedConstructorDto $mixedConstructorDto */
        $mixedConstructorDto = $this->mockContainer->getMock(MixedConstructorDto::class);

        static::assertInstanceOf(MockInterface::class, $mixedConstructorDto->getSecondMockDto());
        static::assertInstanceOf(SecondMockDto::class, $mixedConstructorDto->getSecondMockDto());
        static::assertInstanceOf(EventDispatcherInterface::class, $mixedConstructorDto->getEventDispatcher());
        static::assertSame('instance-name', $mixedConstructorDto->getName());
        static::assertSame(7, $mixedConstructorDto->getCount());
    }

    public function testConstructDependencyMixedTypes(): void
    {
        $mockDto = new MockDto(
            MixedConstructorDto::class,
            [
                new MockDto(SecondMockDto::class),
                EventDispatcherInterfaceMock::class,
                'static-name',
                99,
            ],
            true,
        );

        $this->mockContainer->registerMockDto($mockDto);

        /** @var MixedConstructorDto $mixedConstructorDto */
        $mixedConstructorDto = $this->mockContainer->getMock(MixedConstructorDto::class);

        static::assertInstanceOf(SecondMockDto::class, $mixedConstructorDto->getSecondMockDto());
        static::assertInstanceOf(EventDispatcherInterface::class, $mixedConstructorDto->getEventDispatcher());
        static::assertSame('static-name', $mixedConstructorDto->getName());
        static::assertSame(99, $mixedConstructorDto->getCount());
    }

    public function testDeepNestedChainResolvesThreeLevels(): void
    {
        $mockDto = new MockDto(
            DeepNestedServiceDto::class,
            [
                new MockDto(
                    FirstMockDto::class,
                    [
                        new MockDto(SecondMockDto::class),
                        EventDispatcherInterfaceMock::class,
                        ManagerRegistryMock::class,
                        SluggerInterfaceMock::class,
                    ],
                    true,
                ),
                'secret-api-key',
            ],
            true,
        );

        $this->mockContainer->registerMockDto($mockDto);

        /** @var DeepNestedServiceDto $deepNestedServiceDto */
        $deepNestedServiceDto = $this->mockContainer->getMock(DeepNestedServiceDto::class);

        static::assertInstanceOf(MockInterface::class, $deepNestedServiceDto);
        static::assertInstanceOf(DeepNestedServiceDto::class, $deepNestedServiceDto);
        static::assertSame('secret-api-key', $deepNestedServiceDto->getApiKey());

        /** @var FirstMockDto $firstMockDto */
        $firstMockDto = $deepNestedServiceDto->getFirstMockDto();

        static::assertInstanceOf(MockInterface::class, $firstMockDto);
        static::assertInstanceOf(FirstMockDto::class, $firstMockDto);
        static::assertInstanceOf(SecondMockDto::class, $firstMockDto->getSecondMockDto());
        static::assertInstanceOf(EventDispatcherInterface::class, $firstMockDto->getEventDispatcher());
        static::assertInstanceOf(ManagerRegistry::class, $firstMockDto->getManagerRegistry());
        static::assertInstanceOf(SluggerInterface::class, $firstMockDto->getSlugger());
    }

    public function testConstructDependencyWithNullableParametersUsingDefaults(): void
    {
        $mockDto = new MockDto(
            NullableConstructorDto::class,
            [
                'required-name',
            ],
            true,
        );

        $this->mockContainer->registerMockDto($mockDto);

        /** @var NullableConstructorDto $nullableConstructorDto */
        $nullableConstructorDto = $this->mockContainer->getMock(NullableConstructorDto::class);

        static::assertSame('required-name', $nullableConstructorDto->getName());
        static::assertNull($nullableConstructorDto->getSecondMockDto());
        static::assertNull($nullableConstructorDto->getPriority());
    }

    public function testConstructDependencyWithNullableParametersMixedWithMocks(): void
    {
        $mockDto = new MockDto(
            NullableConstructorDto::class,
            [
                'with-mock',
                new MockDto(SecondMockDto::class),
                42,
            ],
            true,
        );

        $this->mockContainer->registerMockDto($mockDto);

        /** @var NullableConstructorDto $nullableConstructorDto */
        $nullableConstructorDto = $this->mockContainer->getMock(NullableConstructorDto::class);

        static::assertSame('with-mock', $nullableConstructorDto->getName());
        static::assertInstanceOf(MockInterface::class, $nullableConstructorDto->getSecondMockDto());
        static::assertInstanceOf(SecondMockDto::class, $nullableConstructorDto->getSecondMockDto());
        static::assertSame(42, $nullableConstructorDto->getPriority());
    }
}
