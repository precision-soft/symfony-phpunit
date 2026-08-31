<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Utility;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\MockInterface;
use PrecisionSoft\Symfony\Phpunit\Mock\ManagerRegistryMock;

class ExposedManagerRegistryMock extends ManagerRegistryMock
{
    /**
     * @param MockInterface&EntityManagerInterface $entityManagerMock
     * @param class-string $expectedClass
     * @param Closure(string): mixed $factory
     */
    public static function configureCachedFactory(
        MockInterface $entityManagerMock,
        string $methodName,
        string $expectedClass,
        Closure $factory,
    ): void {
        parent::configureCachedFactory($entityManagerMock, $methodName, $expectedClass, $factory);
    }
}
