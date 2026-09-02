<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Example\Test\Container;

use Mockery;
use PrecisionSoft\Symfony\Phpunit\Example\Service\CategoryService;
use PrecisionSoft\Symfony\Phpunit\Example\Test\Utility\ExchangeRateService;
use PrecisionSoft\Symfony\Phpunit\Example\Test\Utility\PriceListService;
use PrecisionSoft\Symfony\Phpunit\Exception\CircularDependencyException;
use PrecisionSoft\Symfony\Phpunit\Exception\MockAlreadyRegisteredException;
use PrecisionSoft\Symfony\Phpunit\Exception\MockClassMismatchException;
use PrecisionSoft\Symfony\Phpunit\Exception\MockNotFoundException;
use PrecisionSoft\Symfony\Phpunit\Mock\ManagerRegistryMock;
use PrecisionSoft\Symfony\Phpunit\Mock\SluggerInterfaceMock;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use PrecisionSoft\Symfony\Phpunit\TestCase\AbstractTestCase;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class MockRegistrationTest extends AbstractTestCase
{
    public static function getMockDto(): MockDto
    {
        return new MockDto(CategoryService::class, [ManagerRegistryMock::class]);
    }

    public function testAnUndeclaredCollaboratorIsReported(): void
    {
        $this->expectException(MockNotFoundException::class);

        $this->get(SluggerInterface::class);
    }

    public function testACollaboratorCanBeDeclaredAtRuntime(): void
    {
        $this->registerMockDto(SluggerInterfaceMock::getMockDto());

        static::assertSame('espresso machine', $this->get(SluggerInterface::class)->slug('espresso machine')->toString());
    }

    public function testAReadyMadeDoubleCanBeRegistered(): void
    {
        $eventDispatcherMock = Mockery::mock(EventDispatcherInterface::class);

        $this->registerMock(EventDispatcherInterface::class, $eventDispatcherMock);

        static::assertSame($eventDispatcherMock, $this->get(EventDispatcherInterface::class));
    }

    public function testADoubleOfAnotherClassIsRejected(): void
    {
        $this->expectException(MockClassMismatchException::class);

        $this->registerMock(SluggerInterface::class, Mockery::mock(EventDispatcherInterface::class));
    }

    public function testDeclaringTheSameClassTwiceIsRejected(): void
    {
        $this->expectException(MockAlreadyRegisteredException::class);

        $this->registerMockDto(new MockDto(CategoryService::class));
    }

    public function testACircularConstructionIsDetected(): void
    {
        $this->registerMockDto(
            new MockDto(
                PriceListService::class,
                [new MockDto(ExchangeRateService::class, [new MockDto(PriceListService::class)])],
            ),
        );

        $this->expectException(CircularDependencyException::class);

        $this->get(PriceListService::class);
    }
}
