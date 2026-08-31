<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\TestCase\Trait;

use Closure;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\Exception\MockContainerNotInitializedException;
use PrecisionSoft\Symfony\Phpunit\MockDto;

trait MockContainerTrait
{
    use MockeryPHPUnitIntegration;

    protected ?MockContainer $mockContainer = null;

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return MockInterface&T
     */
    protected function get(string $class): MockInterface
    {
        if (null === $this->mockContainer) {
            throw new MockContainerNotInitializedException(
                'mock container is not initialized',
            );
        }

        return $this->mockContainer->getMock($class);
    }

    protected function registerMockDto(MockDto $mockDto): static
    {
        $this->initializeMockContainer()->registerMockDto($mockDto);

        return $this;
    }

    /** @param class-string $class */
    protected function registerMock(string $class, MockInterface $mockInterface): static
    {
        $this->initializeMockContainer()->registerMock($class, $mockInterface);

        return $this;
    }

    /**
     * @template T of object
     * @template R
     * @param class-string<T> $class
     * @param MockInterface&T $mockInterface
     * @param Closure(MockInterface&T, MockContainer): R $callback
     * @return R
     */
    protected function withMock(string $class, MockInterface $mockInterface, Closure $callback): mixed
    {
        return $this->initializeMockContainer()->withMock($class, $mockInterface, $callback);
    }

    protected function initializeMockContainer(): MockContainer
    {
        return $this->mockContainer ??= new MockContainer();
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (true === \method_exists(static::class, 'getMockDto')) {
            $this->registerMockDto(static::getMockDto());

            return;
        }

        $this->initializeMockContainer();
    }

    protected function tearDown(): void
    {
        $this->mockContainer?->close();
        $this->mockContainer = null;

        parent::tearDown();
    }
}
