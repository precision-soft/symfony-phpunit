<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Example\Test\Service;

use Doctrine\Persistence\ManagerRegistry;
use PrecisionSoft\Symfony\Phpunit\Example\Entity\Category;
use PrecisionSoft\Symfony\Phpunit\Example\Entity\Product;
use PrecisionSoft\Symfony\Phpunit\Example\Exception\UnmanagedEntityException;
use PrecisionSoft\Symfony\Phpunit\Example\Service\CategoryService;
use PrecisionSoft\Symfony\Phpunit\Mock\ManagerRegistryMock;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use PrecisionSoft\Symfony\Phpunit\TestCase\AbstractTestCase;

/**
 * @internal
 */
final class CategoryServiceTest extends AbstractTestCase
{
    public static function getMockDto(): MockDto
    {
        return new MockDto(
            CategoryService::class,
            [ManagerRegistryMock::class],
            true,
        );
    }

    public function testGetReferenceBuildsTheEntityWithItsIdentifier(): void
    {
        $category = $this->get(CategoryService::class)->getReference(7);

        static::assertInstanceOf(Category::class, $category);
        static::assertSame(7, $category->getId());
    }

    public function testGetReferenceRejectsAnEntityDoctrineDoesNotManage(): void
    {
        $categoryService = $this->get(CategoryService::class);

        ManagerRegistryMock::configureManagedEntityClasses($this->get(ManagerRegistry::class), [Product::class]);

        $this->expectException(UnmanagedEntityException::class);

        $categoryService->getReference(7);
    }
}
