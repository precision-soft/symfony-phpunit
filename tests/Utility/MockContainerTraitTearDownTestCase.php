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

class MockContainerTraitTearDownTestCase extends TestCase implements MockDtoInterface
{
    use MockContainerTrait {
        tearDown as public traitTearDown;
    }

    public static function getMockDto(): MockDto
    {
        return new MockDto(SecondMockDto::class);
    }

    public function testNothing(): void
    {
        $this->addToAssertionCount(1);
    }
}
