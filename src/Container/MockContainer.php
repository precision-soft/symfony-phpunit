<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Container;

use Mockery;
use Mockery\MockInterface;
use PrecisionSoft\Symfony\Phpunit\Contract\MockDtoInterface;
use PrecisionSoft\Symfony\Phpunit\Exception\Exception;
use PrecisionSoft\Symfony\Phpunit\MockDto;

class MockContainer
{
    /** @var array<string, MockDto> */
    private array $mockDtos = [];
    /** @var array<string, MockInterface> */
    private array $mocks = [];

    public function registerMockDto(MockDto $mockDto): self
    {
        if (isset($this->mockDtos[$mockDto->getClass()])) {
            throw new Exception(
                \sprintf('mock dto already registered for class `%s`', $mockDto->getClass()),
            );
        }

        $this->mockDtos[$mockDto->getClass()] = $mockDto;

        return $this;
    }

    public function getMock(string $class): MockInterface
    {
        if (!isset($this->mocks[$class])) {
            if (!isset($this->mockDtos[$class])) {
                throw new Exception(\sprintf('no mock dto found for class `%s`', $class));
            }

            $this->createMock($this->mockDtos[$class]);
        }

        return $this->mocks[$class];
    }

    public function registerMock(string $class, MockInterface $mock): self
    {
        if (isset($this->mocks[$class])) {
            throw new Exception(
                \sprintf('mock already registered for class `%s`', $class),
            );
        }

        $this->mocks[$class] = $mock;

        return $this;
    }

    public function close(): void
    {
        $this->mockDtos = [];
        $this->mocks = [];

        Mockery::close();
    }

    private function getOrCreateMock(MockDto $mockDto): MockInterface
    {
        if (isset($this->mocks[$mockDto->getClass()])) {
            return $this->mocks[$mockDto->getClass()];
        }

        return $this->createMock($mockDto);
    }

    private function createMock(MockDto $mockDto): MockInterface
    {
        $mockedConstruct = [];

        foreach ($mockDto->getConstruct() ?? [] as $dependency) {
            switch (true) {
                case $dependency instanceof MockDto:
                    $mockedDependency = $this->getOrCreateMock($dependency);
                    break;
                case $dependency instanceof MockDtoInterface || \is_a($dependency, MockDtoInterface::class, true):
                    $mockedDependency = $this->getOrCreateMock($dependency::getMockDto());
                    break;
                default:
                    $mockedDependency = $dependency;
                    break;
            }

            $mockedConstruct[] = $mockedDependency;
        }

        $mock = $mockDto->getConstruct() === null ?
            Mockery::mock($mockDto->getClass()) : Mockery::mock($mockDto->getClass(), $mockedConstruct);

        $this->registerMock($mockDto->getClass(), $mock);

        if ($mockDto->getPartial()) {
            $mock->makePartial();
        }

        if (null !== $mockDto->getOnCreate()) {
            $mockDto->getOnCreate()($mock, $this);
        }

        return $mock;
    }
}
