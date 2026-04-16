<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test;

use PHPUnit\Framework\TestCase;
use PrecisionSoft\Symfony\Phpunit\Exception\ClassNotFoundException;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use PrecisionSoft\Symfony\Phpunit\Test\Utility\SecondMockDto;

/**
 * @internal
 */
final class MockDtoTest extends TestCase
{
    public function testGetClassReturnsConstructorValue(): void
    {
        $mockDto = new MockDto(SecondMockDto::class);

        static::assertSame(SecondMockDto::class, $mockDto->getClass());
    }

    public function testGetConstructDefaultsToNull(): void
    {
        $mockDto = new MockDto(SecondMockDto::class);

        static::assertNull($mockDto->getConstruct());
    }

    public function testGetPartialDefaultsToFalse(): void
    {
        $mockDto = new MockDto(SecondMockDto::class);

        static::assertFalse($mockDto->getPartial());
    }

    public function testGetOnCreateDefaultsToNull(): void
    {
        $mockDto = new MockDto(SecondMockDto::class);

        static::assertNull($mockDto->getOnCreate());
    }

    public function testGetConstructWithArray(): void
    {
        $construct = ['dep1', 'dep2'];
        $mockDto = new MockDto(SecondMockDto::class, $construct);

        static::assertSame($construct, $mockDto->getConstruct());
    }

    public function testGetConstructWithEmptyArray(): void
    {
        $mockDto = new MockDto(SecondMockDto::class, []);

        static::assertSame([], $mockDto->getConstruct());
    }

    public function testGetPartialTrue(): void
    {
        $mockDto = new MockDto(SecondMockDto::class, null, true);

        static::assertTrue($mockDto->getPartial());
    }

    public function testGetOnCreateWithClosure(): void
    {
        $closure = static function (): void {};
        $mockDto = new MockDto(SecondMockDto::class, null, false, $closure);

        static::assertSame($closure, $mockDto->getOnCreate());
    }

    public function testConstructorRejectsNonExistentClass(): void
    {
        $this->expectException(ClassNotFoundException::class);
        $this->expectExceptionMessage('class `PrecisionSoft\\Symfony\\Phpunit\\Test\\DoesNotExist` does not exist');

        /** @phpstan-ignore-next-line */
        new MockDto('PrecisionSoft\\Symfony\\Phpunit\\Test\\DoesNotExist');
    }
}
