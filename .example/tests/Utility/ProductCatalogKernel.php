<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Example\Test\Utility;

use Doctrine\Persistence\ManagerRegistry;
use PrecisionSoft\Symfony\Phpunit\Example\Service\CategoryService;
use PrecisionSoft\Symfony\Phpunit\Example\Service\ProductCatalogService;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

final class ProductCatalogKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [new FrameworkBundle()];
    }

    public function getCacheDir(): string
    {
        return \dirname(__DIR__, 2) . '/var/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return \dirname(__DIR__, 2) . '/var/log';
    }

    protected function configureContainer(ContainerConfigurator $containerConfigurator): void
    {
        $containerConfigurator->extension(
            'framework',
            [
                'secret' => 'product-catalogue',
                'test' => true,
            ],
        );

        $services = $containerConfigurator->services()
            ->defaults()
            ->autowire();

        $services->set(ManagerRegistry::class)
            ->synthetic()
            ->public();

        $services->set(CategoryService::class);

        $services->set(ProductCatalogService::class)
            ->public();
    }
}
