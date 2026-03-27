<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit;

use Closure;
use PrecisionSoft\Symfony\Phpunit\Contract\MockDtoInterface;

class MockDto
{
    public function __construct(
        private readonly string $class,
        private readonly ?array $construct = null,
        private readonly bool $partial = false,
        private readonly ?Closure $onCreate = null,
    ) {}

    public function getClass(): string
    {
        return $this->class;
    }

    /** @return MockDtoInterface[]|string[] */
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
