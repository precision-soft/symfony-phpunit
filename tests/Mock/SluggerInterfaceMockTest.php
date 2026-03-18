<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Mock;

use PHPUnit\Framework\TestCase;
use PrecisionSoft\Symfony\Phpunit\Container\MockContainer;
use PrecisionSoft\Symfony\Phpunit\Mock\SluggerInterfaceMock;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

/**
 * @internal
 */
final class SluggerInterfaceMockTest extends TestCase
{
    private MockContainer $mockContainer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockContainer = new MockContainer();
        $this->mockContainer->registerMockDto(SluggerInterfaceMock::getMockDto());
    }

    protected function tearDown(): void
    {
        $this->mockContainer->close();

        parent::tearDown();
    }

    public function testGetMockDtoTargetsSluggerInterface(): void
    {
        $mockDto = SluggerInterfaceMock::getMockDto();

        static::assertSame(SluggerInterface::class, $mockDto->getClass());
    }

    public function testGetMockDtoHasNullConstruct(): void
    {
        $mockDto = SluggerInterfaceMock::getMockDto();

        static::assertNull($mockDto->getConstruct());
    }

    public function testGetMockDtoIsNotPartial(): void
    {
        $mockDto = SluggerInterfaceMock::getMockDto();

        static::assertFalse($mockDto->getPartial());
    }

    public function testGetMockDtoHasOnCreateCallback(): void
    {
        $mockDto = SluggerInterfaceMock::getMockDto();

        static::assertNotNull($mockDto->getOnCreate());
    }

    public function testMockImplementsSluggerInterface(): void
    {
        $mock = $this->mockContainer->getMock(SluggerInterface::class);

        static::assertInstanceOf(SluggerInterface::class, $mock);
    }

    public function testSlugReturnsUnicodeString(): void
    {
        $mock = $this->mockContainer->getMock(SluggerInterface::class);

        $result = $mock->slug('some text');

        static::assertInstanceOf(UnicodeString::class, $result);
    }

    public function testSlugResultIsNonEmpty(): void
    {
        $mock = $this->mockContainer->getMock(SluggerInterface::class);

        $result = $mock->slug('any text');

        static::assertGreaterThan(0, \strlen((string)$result));
    }
}
