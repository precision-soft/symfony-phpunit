<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Example\Test\Service;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Mockery;
use Mockery\MockInterface;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\Example\Entity\Category;
use PrecisionSoft\Symfony\Phpunit\Example\Entity\Product;
use PrecisionSoft\Symfony\Phpunit\Example\Event\ProductCreatedEvent;
use PrecisionSoft\Symfony\Phpunit\Example\Exception\ProductNotFoundException;
use PrecisionSoft\Symfony\Phpunit\Example\Service\CategoryService;
use PrecisionSoft\Symfony\Phpunit\Example\Service\ProductCatalogService;
use PrecisionSoft\Symfony\Phpunit\Mock\EventDispatcherInterfaceMock;
use PrecisionSoft\Symfony\Phpunit\Mock\ManagerRegistryMock;
use PrecisionSoft\Symfony\Phpunit\Mock\SluggerInterfaceMock;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use PrecisionSoft\Symfony\Phpunit\TestCase\AbstractTestCase;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class ProductCatalogServiceTest extends AbstractTestCase
{
    public static function getMockDto(): MockDto
    {
        return new MockDto(
            ProductCatalogService::class,
            [
                ManagerRegistryMock::class,
                SluggerInterfaceMock::class,
                EventDispatcherInterfaceMock::class,
                new MockDto(
                    CategoryService::class,
                    [ManagerRegistryMock::class],
                    true,
                    static function (MockInterface $mockInterface): void {
                        $mockInterface->shouldReceive('getReference')
                            ->byDefault()
                            ->andReturnUsing(
                                static fn(int $categoryId): Category => (new Category())
                                    ->setId($categoryId)
                                    ->setName('uncategorised'),
                            );
                    },
                ),
            ],
            true,
        );
    }

    public function testCreateSlugifiesPersistsAndDispatches(): void
    {
        $productCatalogService = $this->get(ProductCatalogService::class);

        $entityManagerMock = $this->get(EntityManagerInterface::class);
        $entityManagerMock->shouldReceive('persist')
            ->once()
            ->with(Mockery::type(Product::class));
        $entityManagerMock->shouldReceive('flush')
            ->once();

        $this->get(EventDispatcherInterface::class)
            ->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(ProductCreatedEvent::class))
            ->andReturnUsing(static fn(object $event): object => $event);

        $product = $productCatalogService->create('Espresso Machine', 7, 24900);

        static::assertSame('Espresso Machine', $product->getName());
        static::assertSame('espresso machine', $product->getSlug());
        static::assertSame(24900, $product->getPrice());

        $category = $product->getCategory();

        static::assertInstanceOf(Category::class, $category);
        static::assertSame(7, $category->getId());
        static::assertSame('uncategorised', $category->getName());
    }

    public function testRenameFindsTheProductThroughTheRepositoryFactory(): void
    {
        $productCatalogService = $this->get(ProductCatalogService::class);

        $product = (new Product())
            ->setName('Espresso Machine')
            ->setSlug('espresso-machine');

        /** @var EntityRepository<object>&MockInterface $repositoryMock */
        $repositoryMock = Mockery::mock(EntityRepository::class);
        $repositoryMock->shouldReceive('findOneBy')
            ->once()
            ->with(['slug' => 'espresso-machine'])
            ->andReturn($product);

        $entityManagerMock = $this->get(EntityManagerInterface::class);
        ManagerRegistryMock::configureRepositoryFactory(
            $entityManagerMock,
            static fn(string $entityClass): MockInterface => $repositoryMock,
        );
        $entityManagerMock->shouldReceive('flush')
            ->once();

        $renamed = $productCatalogService->rename('espresso-machine', 'Espresso Machine Pro');

        static::assertSame($product, $renamed);
        static::assertSame('Espresso Machine Pro', $renamed->getName());
    }

    public function testRenameRejectsAnUnknownSlug(): void
    {
        $productCatalogService = $this->get(ProductCatalogService::class);

        /** @var EntityRepository<object>&MockInterface $repositoryMock */
        $repositoryMock = Mockery::mock(EntityRepository::class);
        $repositoryMock->shouldReceive('findOneBy')
            ->once()
            ->andReturnNull();

        ManagerRegistryMock::configureRepositoryFactory(
            $this->get(EntityManagerInterface::class),
            static fn(string $entityClass): MockInterface => $repositoryMock,
        );

        $this->expectException(ProductNotFoundException::class);
        $this->expectExceptionMessage('no product with slug `grinder`');

        $productCatalogService->rename('grinder', 'Grinder');
    }

    public function testDescribeReadsTheClassMetadataFactory(): void
    {
        $productCatalogService = $this->get(ProductCatalogService::class);

        /** @var ClassMetadata<Product>&MockInterface $classMetadataMock */
        $classMetadataMock = Mockery::mock(ClassMetadata::class);
        $classMetadataMock->shouldReceive('getFieldNames')
            ->once()
            ->andReturn(['id', 'name', 'slug', 'price']);

        ManagerRegistryMock::configureClassMetadataFactory(
            $this->get(EntityManagerInterface::class),
            static fn(string $entityClass): MockInterface => $classMetadataMock,
        );

        static::assertSame(['id', 'name', 'slug', 'price'], $productCatalogService->describe());
    }

    public function testRemoveBySlugRunsOneStatementOnTheConnection(): void
    {
        $productCatalogService = $this->get(ProductCatalogService::class);

        $this->get(Connection::class)
            ->shouldReceive('executeStatement')
            ->once()
            ->with('DELETE FROM product WHERE slug = :slug', ['slug' => 'espresso-machine'])
            ->andReturn(1);

        static::assertSame(1, $productCatalogService->removeBySlug('espresso-machine'));
    }

    public function testImportWrapsEveryRowInOneTransaction(): void
    {
        $productCatalogService = $this->get(ProductCatalogService::class);

        $entityManagerMock = $this->get(EntityManagerInterface::class);
        $entityManagerMock->shouldReceive('wrapInTransaction')
            ->once()
            ->andReturnUsing(static fn(callable $callback): mixed => $callback($entityManagerMock));
        $entityManagerMock->shouldReceive('flush')
            ->twice();

        $this->get(EventDispatcherInterface::class)
            ->shouldReceive('dispatch')
            ->twice()
            ->andReturnUsing(static fn(object $event): object => $event);

        $imported = $productCatalogService->import(
            [
                ['name' => 'Espresso Machine', 'categoryId' => 7, 'price' => 24900],
                ['name' => 'Grinder', 'categoryId' => 7, 'price' => 8900],
            ],
        );

        static::assertSame(2, $imported);
    }

    public function testWithMockOverridesTheSluggerForAServiceBuiltInsideTheScope(): void
    {
        $this->registerMockDto(SluggerInterfaceMock::getMockDto());

        $sluggerMock = Mockery::mock(SluggerInterface::class);
        $sluggerMock->shouldReceive('slug')
            ->once()
            ->with('Espresso Machine')
            ->andReturn(new UnicodeString('espresso-machine'));

        $scopedProductCatalogService = null;

        $product = $this->withMock(
            SluggerInterface::class,
            $sluggerMock,
            static function (MockInterface $mockInterface, MockContainer $mockContainer) use (
                &$scopedProductCatalogService,
            ): Product {
                $scopedProductCatalogService = $mockContainer->getMock(ProductCatalogService::class);

                $mockContainer->getMock(EventDispatcherInterface::class)
                    ->shouldReceive('dispatch')
                    ->once()
                    ->andReturnUsing(static fn(object $event): object => $event);

                return $scopedProductCatalogService->create('Espresso Machine', 7, 24900);
            },
        );

        static::assertSame('espresso-machine', $product->getSlug());

        $restoredSluggerMock = $this->get(SluggerInterface::class);

        static::assertNotSame($sluggerMock, $restoredSluggerMock);
        static::assertSame('Espresso Machine', $restoredSluggerMock->slug('Espresso Machine')->toString());

        $productCatalogService = $this->get(ProductCatalogService::class);

        static::assertNotSame($scopedProductCatalogService, $productCatalogService);
        static::assertSame('grinder', $productCatalogService->create('Grinder', 7, 8900)->getSlug());
    }

    public function testTheNestedCategoryServiceSharesTheManagerRegistryMock(): void
    {
        $this->get(ProductCatalogService::class);

        $managerRegistryMock = $this->get(ManagerRegistry::class);

        static::assertInstanceOf(ManagerRegistry::class, $managerRegistryMock);
        static::assertSame($managerRegistryMock->getManager(), $this->get(EntityManagerInterface::class));
    }
}
