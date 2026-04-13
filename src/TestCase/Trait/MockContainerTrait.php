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

    protected ?MockContainer $mockContainer = null;

    abstract public static function getMockDto(): MockDto;

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

    protected function registerMockDto(MockDto $mockDto): self
    {
        $this->initializeMockContainer()->registerMockDto($mockDto);

        return $this;
    }

    /** @param class-string $class */
    protected function registerMock(string $class, MockInterface $mockInterface): self
    {
        $this->initializeMockContainer()->registerMock($class, $mockInterface);

        return $this;
    }

    protected function initializeMockContainer(): MockContainer
    {
        return $this->mockContainer ??= new MockContainer();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerMockDto(static::getMockDto());
    }

    protected function tearDown(): void
    {
        $this->mockContainer?->close();
        $this->mockContainer = null;

        parent::tearDown();
    }
}
