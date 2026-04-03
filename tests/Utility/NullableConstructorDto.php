<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Utility;

class NullableConstructorDto
{
    public function __construct(
        private readonly string $name,
        private readonly ?SecondMockDto $secondMockDto = null,
        private readonly ?int $priority = null,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getSecondMockDto(): ?SecondMockDto
    {
        return $this->secondMockDto;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }
}
