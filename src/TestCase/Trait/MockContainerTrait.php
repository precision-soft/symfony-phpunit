<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\TestCase\Trait;

use Mockery\MockInterface;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\Exception\Exception;
use PrecisionSoft\Symfony\Phpunit\MockDto;

trait MockContainerTrait
{
    private ?MockContainer $mockContainer = null;

    protected function get(string $class): MockInterface
    {
        if (null === $this->mockContainer) {
            throw new Exception(
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
