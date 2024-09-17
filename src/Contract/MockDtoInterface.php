<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Contract;

use PrecisionSoft\Symfony\Phpunit\MockDto;

interface MockDtoInterface
{
    public static function getMockDto(): MockDto;
}
