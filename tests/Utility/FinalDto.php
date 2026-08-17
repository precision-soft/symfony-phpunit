<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Utility;

/** final on purpose: mockery can only build a proxied partial for a final class, which is not an instance of it */
final class FinalDto {}
