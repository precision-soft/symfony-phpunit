<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Dev\PhpStan\Test;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PrecisionSoft\Dev\PhpStan\Rule\YodaComparisonRule;

/**
 * @internal
 * @extends RuleTestCase<YodaComparisonRule>
 */
final class YodaComparisonRuleTest extends RuleTestCase
{
    public function testViolation(): void
    {
        $this->analyse(
            [__DIR__ . '/Fixture/YodaComparison/Violation.php'],
            [
                ['write the constant on the left side of the comparison (yoda style)', 14],
                ['write the constant on the left side of the comparison (yoda style)', 18],
                ['write the constant on the left side of the comparison (yoda style)', 22],
                ['write the constant on the left side of the comparison (yoda style)', 26],
            ],
        );
    }

    public function testClean(): void
    {
        $this->analyse([__DIR__ . '/Fixture/YodaComparison/Clean.php'], []);
    }

    protected function getRule(): Rule
    {
        return new YodaComparisonRule();
    }
}
