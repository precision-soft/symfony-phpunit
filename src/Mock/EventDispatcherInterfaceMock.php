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
            self::getOnCreate(),
        );
    }

    public static function getOnCreate(): Closure
    {
        return static function (MockInterface $mockInterface, MockContainer $mockContainer): void {
            $mockInterface->shouldReceive('dispatch')
                ->byDefault()
                ->andReturnUsing(static function (object $event, ?string $eventName = null): object {
                    return $event;
                });
        };
    }
}
