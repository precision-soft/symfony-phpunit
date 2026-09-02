<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Dev\PhpStan\Test;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PrecisionSoft\Dev\PhpStan\Rule\SqlKeywordCaseRule;

/**
 * @internal
 * @extends RuleTestCase<SqlKeywordCaseRule>
 */
final class SqlKeywordCaseRuleTest extends RuleTestCase
{
    public function testViolation(): void
    {
        $this->analyse(
            [__DIR__ . '/Fixture/SqlKeywordCase/Violation.php'],
            [
                ['sql keyword `select` must be uppercase', 13],
                ['sql keyword `from` must be uppercase', 13],
                ['sql keyword `where` must be uppercase', 13],
                ['sql keyword `order by` must be uppercase', 14],
                ['sql identifier `PRODUCT` must be lowercase (only keywords and functions are uppercase)', 14],
                ['sql keyword `set` must be uppercase', 15],
                ['sql keyword `and` must be uppercase', 16],
            ],
        );
    }

    public function testClean(): void
    {
        $this->analyse([__DIR__ . '/Fixture/SqlKeywordCase/Clean.php'], []);
    }

    protected function getRule(): Rule
    {
        return new SqlKeywordCaseRule();
    }
}
