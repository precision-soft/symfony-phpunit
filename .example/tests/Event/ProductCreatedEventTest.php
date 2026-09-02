<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Example\Test\Event;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use PrecisionSoft\Symfony\Phpunit\Example\Entity\Product;
use PrecisionSoft\Symfony\Phpunit\Example\Event\ProductCreatedEvent;

/**
 * @internal
 */
final class ProductCreatedEventTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testTheEventCarriesTheProduct(): void
    {
        $productMock = Mockery::mock(Product::class);
        $productMock->shouldReceive('getSlug')
            ->once()
            ->andReturn('espresso-machine');

        $productCreatedEvent = new ProductCreatedEvent($productMock);

        static::assertSame('espresso-machine', $productCreatedEvent->getProduct()->getSlug());
    }
}
