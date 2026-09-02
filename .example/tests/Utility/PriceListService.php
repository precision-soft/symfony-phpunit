<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Example\Test\Utility;

class PriceListService
{
    public function __construct(protected ExchangeRateService $exchangeRateService) {}
}
