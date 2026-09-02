<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Dev\PhpStan\Test;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PrecisionSoft\Dev\PhpStan\Rule\LibraryExceptionRule;

/**
 * @internal
 * @extends RuleTestCase<LibraryExceptionRule>
 */
final class LibraryExceptionRuleTest extends RuleTestCase
{
    public function testViolation(): void
    {
        $this->analyse(
            [__DIR__ . '/Fixture/LibraryException/Violation.php'],
            [
                ['built-in exception `InvalidArgumentException` must not be thrown, use a project-specific exception', 14],
                ['built-in exception `RuntimeException` must not be thrown, use a project-specific exception', 17],
            ],
        );
    }

    public function testClean(): void
    {
        $this->analyse([__DIR__ . '/Fixture/LibraryException/Clean.php'], []);
    }

    protected function getRule(): Rule
    {
        return new LibraryExceptionRule(static::createReflectionProvider());
    }
}
