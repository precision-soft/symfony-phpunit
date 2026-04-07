<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\TestCase\Trait;

use PHPUnit\Framework\Attributes\After;
use PrecisionSoft\Symfony\Phpunit\Mock\ManagerRegistryMock;

trait ManagerRegistryMockTrait
{
    #[After]
    protected function resetManagerRegistryMockState(): void
    {
        ManagerRegistryMock::resetManagedEntityClasses();
    }
}
