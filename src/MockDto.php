<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit;

use Closure;
use PrecisionSoft\Symfony\Phpunit\Exception\ClassNotFoundException;

class MockDto
{
    /**
     * @param class-string $class
     * @param list<mixed>|null $construct constructor arguments in order; a nested `MockDto`, a `MockDtoInterface`
     *        and a `class-string<MockDtoInterface>` are resolved into mocks, every other entry is passed through
     * @throws ClassNotFoundException
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

    /** @return list<mixed>|null */
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
