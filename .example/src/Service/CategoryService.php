<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Example\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PrecisionSoft\Symfony\Phpunit\Example\Entity\Category;
use PrecisionSoft\Symfony\Phpunit\Example\Exception\UnmanagedEntityException;

class CategoryService
{
    public function __construct(protected ManagerRegistry $managerRegistry) {}

    public function getReference(int $categoryId): Category
    {
        $category = $this->getEntityManager()->getReference(Category::class, $categoryId);

        if (false === $category instanceof Category) {
            throw new UnmanagedEntityException(\sprintf('no reference for category `%d`', $categoryId));
        }

        return $category;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        $objectManager = $this->managerRegistry->getManagerForClass(Category::class);

        if (false === $objectManager instanceof EntityManagerInterface) {
            throw new UnmanagedEntityException(\sprintf('`%s` is not managed by doctrine orm', Category::class));
        }

        return $objectManager;
    }
}
