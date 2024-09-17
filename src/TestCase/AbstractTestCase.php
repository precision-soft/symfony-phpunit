<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\TestCase;

use PHPUnit\Framework\TestCase;
use PrecisionSoft\Symfony\Phpunit\Contract\MockDtoInterface;
use PrecisionSoft\Symfony\Phpunit\TestCase\Trait\MockContainerTrait;

abstract class AbstractTestCase extends TestCase implements MockDtoInterface
{
    use MockContainerTrait;
}
