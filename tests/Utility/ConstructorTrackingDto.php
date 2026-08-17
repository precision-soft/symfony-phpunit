<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Utility;

/** the properties are public on purpose: mockery intercepts method calls, not property reads */
class ConstructorTrackingDto
{
    public bool $constructorCalled = false;

    public function __construct(public ?SecondMockDto $secondMockDto = null)
    {
        $this->constructorCalled = true;
    }

    public function describe(): string
    {
        return 'original implementation';
    }
}
