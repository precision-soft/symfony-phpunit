<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\TestCase\Trait;

use PHPUnit\Framework\Attributes\After;
use PrecisionSoft\Symfony\Phpunit\Mock\ManagerRegistryMock;

/**
 * @deprecated since 3.3.0, use ManagerRegistryMock::configureManagedEntityClasses() for per-mock scoping.
 *             Will be removed in 4.0.0 together with ManagerRegistryMock::resetManagedEntityClasses().
 */
trait ManagerRegistryMockTrait
{
    #[After]
    protected function resetManagerRegistryMockState(): void
    {
        ManagerRegistryMock::resetManagedEntityClasses();
    }
}
