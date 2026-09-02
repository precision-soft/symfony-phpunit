<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Utility;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    public static function cleanupTempDirs(): void
    {
        $baseDir = static::getBaseTempDir();

        if (false === \is_dir($baseDir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $fileInfo) {
            /** @var SplFileInfo $fileInfo */
            if (true === $fileInfo->isDir()) {
                \rmdir($fileInfo->getPathname());

                continue;
            }

            \unlink($fileInfo->getPathname());
        }

        \rmdir($baseDir);
    }

    protected static function getBaseTempDir(): string
    {
        return \sys_get_temp_dir() . '/symfony-phpunit-test-' . \getmypid();
    }

    public function registerBundles(): iterable
    {
        return [];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void {}

    public function getCacheDir(): string
    {
        return static::getBaseTempDir() . '/cache';
    }

    public function getLogDir(): string
    {
        return static::getBaseTempDir() . '/log';
    }

}
