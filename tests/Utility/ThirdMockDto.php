<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Utility;

use PrecisionSoft\Symfony\Phpunit\Contract\MockDtoInterface;
use PrecisionSoft\Symfony\Phpunit\MockDto;

class ThirdMockDto implements MockDtoInterface
{
    public static function getMockDto(): MockDto
    {
        return new MockDto(SecondMockDto::class);
    }
}
