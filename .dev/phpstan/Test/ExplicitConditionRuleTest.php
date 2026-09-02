<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Dev\PhpStan\Test;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PrecisionSoft\Dev\PhpStan\Rule\ExplicitConditionRule;

/**
 * @internal
 * @extends RuleTestCase<ExplicitConditionRule>
 */
final class ExplicitConditionRuleTest extends RuleTestCase
{
    public function testViolation(): void
    {
        $this->analyse(
            [__DIR__ . '/Fixture/ExplicitCondition/Violation.php'],
            [
                ['condition must be an explicit comparison, found `Expr_Variable`', 14],
                ['condition must be an explicit comparison, found `Expr_Isset`', 18],
                ['condition must be an explicit comparison, found `Expr_FuncCall`', 22],
                ['condition must be an explicit comparison, found `Expr_Instanceof`', 26],
                ['condition must be an explicit comparison, found `Expr_Variable`', 30],
                ['the `?:` operator is forbidden, write the condition and both branches explicitly', 34],
                ['condition must be an explicit comparison, found `Expr_Variable`', 36],
            ],
        );
    }

    public function testClean(): void
    {
        $this->analyse([__DIR__ . '/Fixture/ExplicitCondition/Clean.php'], []);
    }

    protected function getRule(): Rule
    {
        return new ExplicitConditionRule();
    }
}
