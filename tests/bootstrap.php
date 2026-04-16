<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

require \dirname(__DIR__) . '/vendor/autoload.php';

\register_shutdown_function(static function (): void {
    if (true === \class_exists(\PrecisionSoft\Symfony\Phpunit\Test\Utility\TestKernel::class)) {
        \PrecisionSoft\Symfony\Phpunit\Test\Utility\TestKernel::cleanupTempDirs();
    }
});
