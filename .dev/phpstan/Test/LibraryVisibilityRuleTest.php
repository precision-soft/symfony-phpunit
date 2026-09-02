<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Dev\PhpStan\Test;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PrecisionSoft\Dev\PhpStan\Rule\LibraryVisibilityRule;

/**
 * @internal
 * @extends RuleTestCase<LibraryVisibilityRule>
 */
final class LibraryVisibilityRuleTest extends RuleTestCase
{
    public function testViolation(): void
    {
        $this->analyse(
            [__DIR__ . '/Fixture/LibraryVisibility/Src/Violation.php'],
            [
                ['library class `Violation` must not be final', 7],
                ['library method `run()` must not be final', 9],
                ['library method `describe()` must be protected, not private', 14],
            ],
        );
    }

    public function testClean(): void
    {
        $this->analyse([__DIR__ . '/Fixture/LibraryVisibility/Src/Clean.php'], []);
        $this->analyse([__DIR__ . '/Fixture/LibraryVisibility/Outside.php'], []);
    }

    protected function getRule(): Rule
    {
        return new LibraryVisibilityRule([__DIR__ . '/Fixture/LibraryVisibility/Src']);
    }
}
