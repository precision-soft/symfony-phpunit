<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Utility;

class ScalarConstructorDto
{
    public function __construct(
        private readonly string $name,
        private readonly int $count,
        private readonly bool $active,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getActive(): bool
    {
        return $this->active;
    }
}
