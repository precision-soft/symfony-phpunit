<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Container;

use Doctrine\Persistence\ManagerRegistry;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\Mock\EventDispatcherInterfaceMock;
use PrecisionSoft\Symfony\Phpunit\Mock\ManagerRegistryMock;
use PrecisionSoft\Symfony\Phpunit\Mock\SluggerInterfaceMock;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\FirstMockDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\SecondMockDto;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class MockContainerTest extends TestCase
{
    public function test(): void
    {
        $mockContainer = new MockContainer();

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

        $mockContainer->registerMockDto($mockDto);

        /** @var FirstMockDto $mock */
        $mock = $mockContainer->getMock(FirstMockDto::class);

        static::assertInstanceOf(MockInterface::class, $mock);
        static::assertInstanceOf(FirstMockDto::class, $mock);
        static::assertInstanceOf(EventDispatcherInterface::class, $mock->getEventDispatcher());
        static::assertInstanceOf(ManagerRegistry::class, $mock->getManagerRegistry());
        static::assertInstanceOf(SluggerInterface::class, $mock->getSlugger());
    }
}
