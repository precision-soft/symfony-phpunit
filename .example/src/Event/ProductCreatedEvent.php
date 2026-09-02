<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Example\Event;

use PrecisionSoft\Symfony\Phpunit\Example\Entity\Product;

class ProductCreatedEvent
{
    public function __construct(protected Product $product) {}

    public function getProduct(): Product
    {
        return $this->product;
    }
}
