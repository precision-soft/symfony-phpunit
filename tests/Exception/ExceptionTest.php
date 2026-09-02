<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Exception;

use Exception as BaseException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PrecisionSoft\Symfony\Phpunit\Contract\ExceptionInterface;
use PrecisionSoft\Symfony\Phpunit\Exception\CircularDependencyException;
use PrecisionSoft\Symfony\Phpunit\Exception\ClassNotFoundException;
use PrecisionSoft\Symfony\Phpunit\Exception\Exception;
use PrecisionSoft\Symfony\Phpunit\Exception\MockAlreadyRegisteredException;
use PrecisionSoft\Symfony\Phpunit\Exception\MockClassMismatchException;
use PrecisionSoft\Symfony\Phpunit\Exception\MockContainerNotInitializedException;
use PrecisionSoft\Symfony\Phpunit\Exception\MockNotFoundException;

/**
 * @internal
 */
final class ExceptionTest extends TestCase
{
    /** @return iterable<string, array{class-string<Exception>}> */
    public static function provideSubclasses(): iterable
    {
        yield 'circular dependency' => [CircularDependencyException::class];
        yield 'class not found' => [ClassNotFoundException::class];
        yield 'mock already registered' => [MockAlreadyRegisteredException::class];
        yield 'mock class mismatch' => [MockClassMismatchException::class];
        yield 'mock container not initialized' => [MockContainerNotInitializedException::class];
        yield 'mock not found' => [MockNotFoundException::class];
    }

    public function testExceptionExtendsBaseException(): void
    {
        $exception = new Exception('test message');

        static::assertInstanceOf(BaseException::class, $exception);
        static::assertSame('test message', $exception->getMessage());
    }

    public function testExceptionImplementsExceptionInterface(): void
    {
        static::assertInstanceOf(ExceptionInterface::class, new Exception('test message'));
    }

    /** @param class-string<Exception> $class */
    #[DataProvider('provideSubclasses')]
    public function testEverySubclassCarriesTheContextCapability(string $class): void
    {
        $exception = new $class('test message', 0, null, ['key' => 'value']);

        static::assertInstanceOf(Exception::class, $exception);
        static::assertInstanceOf(ExceptionInterface::class, $exception);
        static::assertSame(['key' => 'value'], $exception->getContext());
    }

    public function testContextDefaultsToAnEmptyArray(): void
    {
        static::assertSame([], (new Exception('test message'))->getContext());
        static::assertSame([], (new Exception('test message', 0, null, null))->getContext());
    }

    public function testContextIsReadBackFromTheConstructor(): void
    {
        $exception = new Exception('test message', 0, null, ['class' => 'MyService', 'attempt' => 2]);

        static::assertSame(['class' => 'MyService', 'attempt' => 2], $exception->getContext());
    }

    public function testSetContextReplacesTheContextAndIsFluent(): void
    {
        $exception = new Exception('test message', 0, null, ['first' => 1]);

        static::assertSame($exception, $exception->setContext(['second' => 2]));
        static::assertSame(['second' => 2], $exception->getContext());

        $exception->setContext(null);

        static::assertSame([], $exception->getContext());
    }

    public function testTheContextDoesNotLeakIntoTheMessageCodeOrPrevious(): void
    {
        $previousException = new BaseException('root cause');

        $exception = new Exception('test message', 7, $previousException, ['key' => 'value']);

        static::assertSame('test message', $exception->getMessage());
        static::assertSame(7, $exception->getCode());
        static::assertSame($previousException, $exception->getPrevious());
    }

    public function testTheConstructorDefaultsToAnEmptyMessageZeroCodeAndNoPrevious(): void
    {
        $exception = new Exception();

        static::assertSame('', $exception->getMessage());
        static::assertSame(0, $exception->getCode());
        static::assertNull($exception->getPrevious());
        static::assertSame([], $exception->getContext());
    }
}
