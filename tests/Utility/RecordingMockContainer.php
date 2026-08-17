<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Utility;

use Mockery\MockInterface;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\MockDto;

class RecordingMockContainer extends MockContainer
{
    /** @var list<class-string> */
    protected array $createdClassList = [];
    /** @var list<class-string> */
    protected array $resolvedDependencyClassList = [];

    /** @return list<class-string> */
    public function getCreatedClassList(): array
    {
        return $this->createdClassList;
    }

    /** @return list<class-string> */
    public function getResolvedDependencyClassList(): array
    {
        return $this->resolvedDependencyClassList;
    }

    protected function getOrCreateMock(MockDto $mockDto): MockInterface
    {
        $this->resolvedDependencyClassList[] = $mockDto->getClass();

        return parent::getOrCreateMock($mockDto);
    }

    protected function createMock(MockDto $mockDto): MockInterface
    {
        $this->createdClassList[] = $mockDto->getClass();

        return parent::createMock($mockDto);
    }
}
