<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Utility;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void {}

    public function getCacheDir(): string
    {
        return \sys_get_temp_dir() . '/symfony-phpunit-test/cache';
    }

    public function getLogDir(): string
    {
        return \sys_get_temp_dir() . '/symfony-phpunit-test/log';
    }
}
