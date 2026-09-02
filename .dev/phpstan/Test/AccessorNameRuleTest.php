<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Dev\PhpStan\Test;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PrecisionSoft\Dev\PhpStan\Rule\AccessorNameRule;

/**
 * @internal
 * @extends RuleTestCase<AccessorNameRule>
 */
final class AccessorNameRuleTest extends RuleTestCase
{
    public function testViolation(): void
    {
        $this->analyse(
            [__DIR__ . '/Fixture/AccessorName/Violation.php'],
            [
                ['public method `isReady()` must not use the `is` prefix, name it `getReady()` or `hasReady()`', 11],
                ['public method `is2faEnabled()` must not use the `is` prefix, name it `get2faEnabled()` or `has2faEnabled()`', 16],
            ],
        );
    }

    public function testClean(): void
    {
        $this->analyse([__DIR__ . '/Fixture/AccessorName/Clean.php'], []);
    }

    protected function getRule(): Rule
    {
        return new AccessorNameRule();
    }
}
