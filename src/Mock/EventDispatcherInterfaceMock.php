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
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EventDispatcherInterfaceMock implements MockDtoInterface
{
    public static function getMockDto(): MockDto
    {
        return new MockDto(
            EventDispatcherInterface::class,
            null,
            false,
            static::getOnCreate(),
        );
    }

    public static function getOnCreate(): Closure
    {
        return function (MockInterface $mock, MockContainer $mockContainer): void {
            $mock->shouldReceive('dispatch')
                ->byDefault()
                ->andReturnUsing(function () {
                    return func_get_arg(0);
                });
        };
    }
}
