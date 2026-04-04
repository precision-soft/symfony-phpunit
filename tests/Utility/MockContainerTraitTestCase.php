<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Utility;

use PHPUnit\Framework\TestCase;
use PrecisionSoft\Symfony\Phpunit\Contract\MockDtoInterface;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use PrecisionSoft\Symfony\Phpunit\TestCase\Trait\MockContainerTrait;

class MockContainerTraitTestCase extends TestCase implements MockDtoInterface
{
    use MockContainerTrait {
        get as public;
        registerMockDto as public;
        registerMock as public;
    }

    /** @phpstan-ignore method.parentMethodFinalByPhpDoc */
    public function __construct(?string $name = null)
    {
        parent::__construct($name ?? static::class);
    }

    public static function getMockDto(): MockDto
    {
        return new MockDto(SecondMockDto::class);
    }
}
