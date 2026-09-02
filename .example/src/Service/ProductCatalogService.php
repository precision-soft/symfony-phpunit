<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Example\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PrecisionSoft\Symfony\Phpunit\Example\Entity\Product;
use PrecisionSoft\Symfony\Phpunit\Example\Event\ProductCreatedEvent;
use PrecisionSoft\Symfony\Phpunit\Example\Exception\ProductNotFoundException;
use PrecisionSoft\Symfony\Phpunit\Example\Exception\UnmanagedEntityException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ProductCatalogService
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected SluggerInterface $slugger,
        protected EventDispatcherInterface $eventDispatcher,
        protected CategoryService $categoryService,
    ) {}

    public function create(string $name, int $categoryId, int $price): Product
    {
        $product = (new Product())
            ->setName($name)
            ->setSlug($this->slugger->slug($name)->lower()->toString())
            ->setCategory($this->categoryService->getReference($categoryId))
            ->setPrice($price);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($product);
        $entityManager->flush();

        $this->eventDispatcher->dispatch(new ProductCreatedEvent($product));

        return $product;
    }

    public function rename(string $slug, string $name): Product
    {
        $entityManager = $this->getEntityManager();

        $product = $entityManager->getRepository(Product::class)->findOneBy(['slug' => $slug]);

        if (null === $product) {
            throw new ProductNotFoundException(\sprintf('no product with slug `%s`', $slug));
        }

        $product->setName($name);

        $entityManager->flush();

        return $product;
    }

    /** @return array<int, string> */
    public function describe(): array
    {
        return $this->getEntityManager()->getClassMetadata(Product::class)->getFieldNames();
    }

    public function removeBySlug(string $slug): int
    {
        $affectedRows = $this->getEntityManager()
            ->getConnection()
            ->executeStatement('DELETE FROM product WHERE slug = :slug', ['slug' => $slug]);

        return (int)$affectedRows;
    }

    /** @param list<array{name: string, categoryId: int, price: int}> $rows */
    public function import(array $rows): int
    {
        $imported = $this->getEntityManager()->wrapInTransaction(
            function () use ($rows): int {
                $count = 0;

                foreach ($rows as $row) {
                    $this->create($row['name'], $row['categoryId'], $row['price']);

                    ++$count;
                }

                return $count;
            },
        );

        return (int)$imported;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        $objectManager = $this->managerRegistry->getManagerForClass(Product::class);

        if (false === $objectManager instanceof EntityManagerInterface) {
            throw new UnmanagedEntityException(\sprintf('`%s` is not managed by doctrine orm', Product::class));
        }

        return $objectManager;
    }
}
