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
        return self::getBaseTempDir() . '/cache';
    }

    public function getLogDir(): string
    {
        return self::getBaseTempDir() . '/log';
    }

    public static function cleanupTempDirs(): void
    {
        $baseDir = self::getBaseTempDir();

        if (false === \is_dir($baseDir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $fileInfo) {
            /** @var \SplFileInfo $fileInfo */
            if (true === $fileInfo->isDir()) {
                \rmdir($fileInfo->getPathname());

                continue;
            }

            \unlink($fileInfo->getPathname());
        }

        \rmdir($baseDir);
    }

    private static function getBaseTempDir(): string
    {
        return \sys_get_temp_dir() . '/symfony-phpunit-test-' . \getmypid();
    }
}
