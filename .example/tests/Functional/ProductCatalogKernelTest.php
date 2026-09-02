<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Example\Test\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PrecisionSoft\Symfony\Phpunit\Example\Entity\Category;
use PrecisionSoft\Symfony\Phpunit\Example\Service\ProductCatalogService;
use PrecisionSoft\Symfony\Phpunit\Mock\ManagerRegistryMock;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use PrecisionSoft\Symfony\Phpunit\TestCase\AbstractKernelTestCase;

/**
 * @internal
 */
final class ProductCatalogKernelTest extends AbstractKernelTestCase
{
    public static function getMockDto(): MockDto
    {
        return ManagerRegistryMock::getMockDto();
    }

    public function testTheContainerWiresTheCatalogueAroundTheMockedRegistry(): void
    {
        static::bootKernel();

        $container = static::getContainer();
        $container->set(ManagerRegistry::class, $this->get(ManagerRegistry::class));

        $this->get(EntityManagerInterface::class)
            ->shouldReceive('flush')
            ->once();

        $productCatalogService = $container->get(ProductCatalogService::class);

        static::assertInstanceOf(ProductCatalogService::class, $productCatalogService);

        $product = $productCatalogService->create('Espresso Machine', 7, 24900);

        static::assertSame('espresso-machine', $product->getSlug());

        $category = $product->getCategory();

        static::assertInstanceOf(Category::class, $category);
        static::assertSame(7, $category->getId());
    }
}
