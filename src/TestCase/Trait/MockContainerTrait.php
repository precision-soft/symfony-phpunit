<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\TestCase\Trait;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\Exception\MockContainerNotInitializedException;
use PrecisionSoft\Symfony\Phpunit\MockDto;

trait MockContainerTrait
{
    use MockeryPHPUnitIntegration;

    private ?MockContainer $mockContainer = null;

    /** @param class-string $class */
    protected function get(string $class): MockInterface
    {
        if (null === $this->mockContainer) {
            throw new MockContainerNotInitializedException(
                'mock container is not initialized',
            );
        }

        return $this->mockContainer->getMock($class);
    }

    protected function registerMockDto(MockDto $mockDto): self
    {
        $this->mockContainer ??= new MockContainer();

        $this->mockContainer->registerMockDto($mockDto);

        return $this;
    }

    /** @param class-string $class */
    protected function registerMock(string $class, MockInterface $mockInterface): self
    {
        $this->mockContainer ??= new MockContainer();

        $this->mockContainer->registerMock($class, $mockInterface);

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerMockDto(static::getMockDto());
    }

    protected function tearDown(): void
    {
        $this->mockContainer?->close();

        parent::tearDown();
    }
}
