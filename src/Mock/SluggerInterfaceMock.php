<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Mock;

use Closure;
use Mockery\MockInterface;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\Contract\MockDtoInterface;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

class SluggerInterfaceMock implements MockDtoInterface
{
    public static function getMockDto(): MockDto
    {
        return new MockDto(
            SluggerInterface::class,
            null,
            false,
            self::getOnCreate(),
        );
    }

    public static function getOnCreate(): Closure
    {
        return static function (MockInterface $mockInterface, MockContainer $mockContainer): void {
            $mockInterface->shouldReceive('slug')
                ->byDefault()
                ->andReturnUsing(static function (string $string): UnicodeString {
                    return new UnicodeString($string);
                });
        };
    }
}
