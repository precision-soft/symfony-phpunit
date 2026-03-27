<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Utility;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MixedConstructorDto
{
    public function __construct(
        private readonly SecondMockDto $secondMockDto,
        private readonly EventDispatcherInterface $eventDispatcherInterface,
        private readonly string $name,
        private readonly int $count,
    ) {}

    public function getSecondMockDto(): SecondMockDto
    {
        return $this->secondMockDto;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcherInterface;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
