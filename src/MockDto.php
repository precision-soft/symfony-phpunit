<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit;

use Closure;
use PrecisionSoft\Symfony\Phpunit\Contract\MockDtoInterface;
use PrecisionSoft\Symfony\Phpunit\Exception\ClassNotFoundException;

class MockDto
{
    /**
     * @param class-string $class
     * @param list<MockDto|MockDtoInterface|class-string<MockDtoInterface>|scalar>|null $construct Scalar values in
     *        $construct are passed as-is to Mockery; ensure they match the target class constructor
     *        parameter types.
     * @throws ClassNotFoundException if $class does not reference an existing class or interface
     */
    public function __construct(
        protected readonly string $class,
        protected readonly ?array $construct = null,
        protected readonly bool $partial = false,
        protected readonly ?Closure $onCreate = null,
    ) {
        if (false === \class_exists($class) && false === \interface_exists($class)) {
            throw new ClassNotFoundException(\sprintf('class `%s` does not exist', $class));
        }
    }

    /** @return class-string */
    public function getClass(): string
    {
        return $this->class;
    }

    /** @return list<MockDto|MockDtoInterface|class-string<MockDtoInterface>|scalar>|null */
    public function getConstruct(): ?array
    {
        return $this->construct;
    }

    public function getPartial(): bool
    {
        return $this->partial;
    }

    public function getOnCreate(): ?Closure
    {
        return $this->onCreate;
    }
}
