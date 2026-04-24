<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Container;

use Mockery;
use Mockery\MockInterface;
use PrecisionSoft\Symfony\Phpunit\Contract\MockDtoInterface;
use PrecisionSoft\Symfony\Phpunit\Exception\CircularDependencyException;
use PrecisionSoft\Symfony\Phpunit\Exception\MockAlreadyRegisteredException;
use PrecisionSoft\Symfony\Phpunit\Exception\MockNotFoundException;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use Throwable;

class MockContainer
{
    /** @var array<string, MockDto> */
    protected array $mockDtos = [];
    /** @var array<string, MockInterface> */
    protected array $mocks = [];
    /** @var array<string, true> */
    protected array $creating = [];

    /**
     * Registers a MockDto configuration for deferred mock creation. The mock is created lazily on first call to
     * getMock(). Throws MockAlreadyRegisteredException if a DTO for the same class was already registered.
     *
     * @throws MockAlreadyRegisteredException
     */
    public function registerMockDto(MockDto $mockDto): static
    {
        if (true === isset($this->mockDtos[$mockDto->getClass()])) {
            throw new MockAlreadyRegisteredException(
                \sprintf('mock dto already registered for class `%s`', $mockDto->getClass()),
            );
        }

        if (true === isset($this->mocks[$mockDto->getClass()])) {
            throw new MockAlreadyRegisteredException(
                \sprintf('mock already registered for class `%s`', $mockDto->getClass()),
            );
        }

        $this->mockDtos[$mockDto->getClass()] = $mockDto;

        return $this;
    }

    /**
     * Returns the mock for the given class, creating it lazily from its registered MockDto if not yet instantiated.
     * Throws MockNotFoundException if no MockDto has been registered for the class.
     *
     * @template T of object
     * @param class-string<T> $class
     * @return MockInterface&T
     * @throws MockNotFoundException
     * @throws CircularDependencyException
     */
    public function getMock(string $class): MockInterface
    {
        if (false === isset($this->mocks[$class])) {
            if (false === isset($this->mockDtos[$class])) {
                throw new MockNotFoundException(\sprintf('no mock dto found for class `%s`', $class));
            }

            $this->createMock($this->mockDtos[$class]);
        }

        return $this->mocks[$class];
    }

    /**
     * @param class-string $class
     * @throws MockAlreadyRegisteredException
     */
    public function registerMock(string $class, MockInterface $mockInterface): static
    {
        if (true === isset($this->mocks[$class])) {
            throw new MockAlreadyRegisteredException(
                \sprintf('mock already registered for class `%s`', $class),
            );
        }

        $this->mocks[$class] = $mockInterface;
        unset($this->mockDtos[$class]);

        return $this;
    }

    /**
     * Returns an existing mock for the class described by the DTO, or registers the DTO and creates the mock if none
     * exists yet. The DTO itself validates that the class/interface exists at construction time.
     */
    public function getOrRegisterMock(MockDto $mockDto): MockInterface
    {
        if (true === $this->hasMock($mockDto->getClass())) {
            return $this->getMock($mockDto->getClass());
        }

        $this->registerMockDto($mockDto);

        return $this->getMock($mockDto->getClass());
    }

    /** @param class-string $class */
    public function hasMock(string $class): bool
    {
        return true === isset($this->mocks[$class]) || true === isset($this->mockDtos[$class]);
    }

    public function close(): void
    {
        $this->mockDtos = [];
        $this->mocks = [];
        $this->creating = [];

        Mockery::close();
    }

    /**
     * Creates or returns an existing mock for the given class. Override in subclasses to customize mock creation
     * logic for sub-dependencies. Called internally by createMock() when resolving MockDto constructor arguments.
     */
    protected function getOrCreateMock(MockDto $mockDto): MockInterface
    {
        if (true === isset($this->mocks[$mockDto->getClass()])) {
            return $this->mocks[$mockDto->getClass()];
        }

        return $this->createMock($mockDto);
    }

    protected function createMock(MockDto $mockDto): MockInterface
    {
        if (true === isset($this->creating[$mockDto->getClass()])) {
            throw new CircularDependencyException(
                \sprintf('circular dependency detected for class `%s`', $mockDto->getClass()),
            );
        }

        $this->creating[$mockDto->getClass()] = true;

        try {
            $mockedConstructorArguments = [];

            foreach ($mockDto->getConstruct() ?? [] as $dependency) {
                if (true === $dependency instanceof MockDto) {
                    $mockedConstructorArguments[] = $this->getOrCreateMock($dependency);

                    continue;
                }

                if (true === $dependency instanceof MockDtoInterface) {
                    $mockedConstructorArguments[] = $this->getOrCreateMock($dependency::getMockDto());

                    continue;
                }

                if (true === \is_string($dependency) && true === \is_a($dependency, MockDtoInterface::class, true)) {
                    /** @var class-string<MockDtoInterface> $dependency */
                    $mockedConstructorArguments[] = $this->getOrCreateMock($dependency::getMockDto());

                    continue;
                }

                $mockedConstructorArguments[] = $dependency;
            }

            if (null === $mockDto->getConstruct()) {
                $mockInterface = Mockery::mock($mockDto->getClass());
            } else {
                $mockInterface = Mockery::mock($mockDto->getClass(), $mockedConstructorArguments);
            }

            $this->registerMock($mockDto->getClass(), $mockInterface);

            if (true === $mockDto->getPartial()) {
                $mockInterface->makePartial();
            }

            try {
                $onCreateClosure = $mockDto->getOnCreate();

                if (null !== $onCreateClosure) {
                    $onCreateClosure($mockInterface, $this);
                }
            } catch (Throwable $throwable) {
                unset($this->mocks[$mockDto->getClass()]);

                throw $throwable;
            }

            return $mockInterface;
        } finally {
            unset($this->creating[$mockDto->getClass()]);
        }
    }
}
