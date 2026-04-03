<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Utility;

class DeepNestedServiceDto
{
    public function __construct(
        private readonly FirstMockDto $firstMockDto,
        private readonly string $apiKey,
    ) {}

    public function getFirstMockDto(): FirstMockDto
    {
        return $this->firstMockDto;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }
}
