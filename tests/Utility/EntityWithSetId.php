<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Utility;

class EntityWithSetId
{
    private mixed $id = null;

    public function setId(mixed $id): void
    {
        $this->id = $id;
    }

    public function getId(): mixed
    {
        return $this->id;
    }
}
