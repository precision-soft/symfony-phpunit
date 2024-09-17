<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Utility;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class FirstMockDto
{
    private SecondMockDto $secondMockDto;
    private EventDispatcherInterface $eventDispatcher;
    private ManagerRegistry $managerRegistry;
    private SluggerInterface $slugger;

    public function __construct(
        SecondMockDto $secondMockDto,
        EventDispatcherInterface $eventDispatcher,
        ManagerRegistry $managerRegistry,
        SluggerInterface $slugger,
    ) {
        $this->secondMockDto = $secondMockDto;
        $this->eventDispatcher = $eventDispatcher;
        $this->managerRegistry = $managerRegistry;
        $this->slugger = $slugger;
    }

    public function getSecondMockDto(): SecondMockDto
    {
        return $this->secondMockDto;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function getManagerRegistry(): ManagerRegistry
    {
        return $this->managerRegistry;
    }

    public function getSlugger(): SluggerInterface
    {
        return $this->slugger;
    }
}
