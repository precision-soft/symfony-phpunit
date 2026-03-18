<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test;

use PHPUnit\Framework\TestCase;
use PrecisionSoft\Symfony\Phpunit\MockDto;

/**
 * @internal
 */
final class MockDtoTest extends TestCase
{
    public function testGetClassReturnsConstructorValue(): void
    {
        $mockDto = new MockDto('SomeClass');

        static::assertSame('SomeClass', $mockDto->getClass());
    }

    public function testGetConstructDefaultsToNull(): void
    {
        $mockDto = new MockDto('SomeClass');

        static::assertNull($mockDto->getConstruct());
    }

    public function testGetPartialDefaultsToFalse(): void
    {
        $mockDto = new MockDto('SomeClass');

        static::assertFalse($mockDto->getPartial());
    }

    public function testGetOnCreateDefaultsToNull(): void
    {
        $mockDto = new MockDto('SomeClass');

        static::assertNull($mockDto->getOnCreate());
    }

    public function testGetConstructWithArray(): void
    {
        $construct = ['dep1', 'dep2'];
        $mockDto = new MockDto('SomeClass', $construct);

        static::assertSame($construct, $mockDto->getConstruct());
    }

    public function testGetConstructWithEmptyArray(): void
    {
        $mockDto = new MockDto('SomeClass', []);

        static::assertSame([], $mockDto->getConstruct());
    }

    public function testGetPartialTrue(): void
    {
        $mockDto = new MockDto('SomeClass', null, true);

        static::assertTrue($mockDto->getPartial());
    }

    public function testGetOnCreateWithClosure(): void
    {
        $closure = static function (): void {};
        $mockDto = new MockDto('SomeClass', null, false, $closure);

        static::assertSame($closure, $mockDto->getOnCreate());
    }
}
