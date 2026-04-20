<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

use PrecisionSoft\Symfony\Phpunit\Test\Utility\TestKernel;

require \dirname(__DIR__) . '/vendor/autoload.php';

\register_shutdown_function(static function (): void {
    if (true === \class_exists(TestKernel::class)) {
        TestKernel::cleanupTempDirs();
    }
});
